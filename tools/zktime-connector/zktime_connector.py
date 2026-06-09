import argparse
import hashlib
import json
import re
import sys
import time
import urllib.error
import urllib.request
from datetime import datetime
from pathlib import Path


DATE_RE = re.compile(r"\b(20\d{2}-\d{2}-\d{2})\b")
TIME_RE = re.compile(r"\b(\d{2}:\d{2}:\d{2})\b")


def load_json(path, default):
    if not path.exists():
        return default
    return json.loads(path.read_text(encoding="utf-8"))


def save_json(path, data):
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def read_text(path):
    for encoding in ("utf-8-sig", "utf-16", "cp1252", "latin-1"):
        try:
            return path.read_text(encoding=encoding)
        except UnicodeDecodeError:
            continue
    return path.read_bytes().decode("latin-1", errors="replace")


def clean(value):
    value = (value or "").strip()
    return value if value else None


def parse_tab_line(line):
    parts = [part.strip() for part in line.rstrip("\n\r").split("\t")]
    if len(parts) < 8:
        return None

    return {
        "employee_code": clean(parts[0]),
        "first_name": clean(parts[1]) if len(parts) > 1 else None,
        "last_name": clean(parts[2]) if len(parts) > 2 else None,
        "department_code": clean(parts[3]) if len(parts) > 3 else None,
        "department_name": clean(parts[4]) if len(parts) > 4 else None,
        "date": clean(parts[5]) if len(parts) > 5 else None,
        "time": clean(parts[6]) if len(parts) > 6 else None,
        "verify_type": clean(parts[7]) if len(parts) > 7 else None,
        "punch_state": clean(parts[8]) if len(parts) > 8 else None,
        "work_code": clean(parts[9]) if len(parts) > 9 else None,
        "card_number": clean(parts[10]) if len(parts) > 10 else None,
        "area_name": clean(parts[11]) if len(parts) > 11 else None,
        "terminal_alias": clean(parts[12]) if len(parts) > 12 else None,
        "terminal_sn": clean(parts[13]) if len(parts) > 13 else None,
        "raw_line": line.rstrip("\n\r"),
    }


def parse_fallback_line(line):
    date_match = DATE_RE.search(line)
    time_match = TIME_RE.search(line)
    if not date_match or not time_match:
        return None

    before = line[:date_match.start()].split()
    after = line[time_match.end():].split()
    if not before:
        return None

    employee_code = before[0]
    department_code = before[-2] if len(before) >= 2 and before[-2].isdigit() else None
    department_name = before[-1] if len(before) >= 2 else None
    name_parts = before[1:-2] if department_code else before[1:]
    employee_name = " ".join(name_parts).strip() or None

    return {
        "employee_code": employee_code,
        "employee_name": employee_name,
        "department_code": department_code,
        "department_name": department_name,
        "date": date_match.group(1),
        "time": time_match.group(1),
        "verify_type": clean(after[0]) if len(after) > 0 else None,
        "punch_state": clean(after[1]) if len(after) > 1 else None,
        "work_code": clean(after[2]) if len(after) > 2 else None,
        "area_name": clean(after[-3]) if len(after) >= 3 else None,
        "terminal_alias": clean(after[-2]) if len(after) >= 2 else None,
        "terminal_sn": clean(after[-1]) if len(after) >= 1 else None,
        "raw_line": line.rstrip("\n\r"),
    }


def valid_record(record):
    if not record or not record.get("employee_code") or not record.get("date") or not record.get("time"):
        return False
    try:
        datetime.strptime(record["date"] + " " + record["time"], "%Y-%m-%d %H:%M:%S")
        return True
    except ValueError:
        return False


def parse_file(path):
    records = []
    for line in read_text(path).splitlines():
        if not line.strip():
            continue
        record = parse_tab_line(line) if "\t" in line else None
        record = record or parse_fallback_line(line)
        if valid_record(record):
            records.append(record)
    return records


def fingerprint(record):
    key = "|".join([
        str(record.get("employee_code") or ""),
        str(record.get("date") or ""),
        str(record.get("time") or ""),
        str(record.get("terminal_sn") or ""),
        str(record.get("raw_line") or ""),
    ])
    return hashlib.sha1(key.encode("utf-8", errors="ignore")).hexdigest()


def post_records(api_url, token, school_id, source_file, records):
    body = json.dumps({
        "school_id": school_id,
        "source_file": source_file,
        "records": records,
    }).encode("utf-8")

    request = urllib.request.Request(
        api_url,
        data=body,
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-ZKTIME-TOKEN": token,
        },
        method="POST",
    )

    with urllib.request.urlopen(request, timeout=45) as response:
        return json.loads(response.read().decode("utf-8"))


def run_once(config_path):
    config = load_json(config_path, {})
    export_dir = Path(config.get("export_dir", "")).resolve()
    state_path = (config_path.parent / config.get("state_file", ".zktime_state.json")).resolve()
    state = load_json(state_path, {"sent": []})
    sent = set(state.get("sent", []))
    batch_size = int(config.get("batch_size", 200))

    if not export_dir.exists():
        raise SystemExit(f"Export directory not found: {export_dir}")

    pending = []
    source_files = []
    for path in sorted(export_dir.glob("*.txt")):
        records = parse_file(path)
        for record in records:
            fp = fingerprint(record)
            if fp in sent:
                continue
            record["_fingerprint"] = fp
            pending.append(record)
            if path.name not in source_files:
                source_files.append(path.name)

    if not pending:
        print("No new ZKBioTime records.", flush=True)
        return 0

    total_sent = 0
    for start in range(0, len(pending), batch_size):
        batch = pending[start:start + batch_size]
        clean_batch = [{k: v for k, v in record.items() if k != "_fingerprint"} for record in batch]
        result = post_records(
            config["api_url"],
            config["token"],
            int(config["school_id"]),
            ", ".join(source_files),
            clean_batch,
        )
        for record in batch:
            sent.add(record["_fingerprint"])
        total_sent += len(batch)
        print(f"Sent {len(batch)} records: {result}", flush=True)

    state["sent"] = sorted(sent)[-50000:]
    save_json(state_path, state)
    print(f"Done. Sent {total_sent} new records.", flush=True)
    return total_sent


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--config", default="config.json")
    parser.add_argument("--watch", action="store_true", help="Keep running and sync new records continuously.")
    parser.add_argument("--interval", type=int, default=60, help="Seconds between sync checks when --watch is enabled.")
    args = parser.parse_args()

    config_path = Path(args.config).resolve()

    if not args.watch:
        run_once(config_path)
        return 0

    interval = max(10, int(args.interval))
    print(f"My Edu ZKBioTime connector started. Checking every {interval} seconds.", flush=True)
    print("Keep this window open. Press Ctrl+C to stop.", flush=True)
    while True:
        try:
            run_once(config_path)
        except Exception as exc:
            print(f"Sync error: {exc}", file=sys.stderr, flush=True)
        time.sleep(interval)

    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except urllib.error.HTTPError as exc:
        print(exc.read().decode("utf-8", errors="replace"), file=sys.stderr)
        raise
