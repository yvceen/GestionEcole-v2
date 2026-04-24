"""Generate the gradient My Edu icon for Capacitor and Android mipmap folders."""
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

FONT_CANDIDATES = [
    Path("C:/Windows/Fonts/arialbd.ttf"),
    Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"),
    Path("/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf"),
]


def load_font(size: int) -> ImageFont.FreeTypeFont:
    for candidate in FONT_CANDIDATES:
        if candidate.exists():
            try:
                return ImageFont.truetype(str(candidate), size)
            except OSError:
                continue
    return ImageFont.load_default()


def create_icon(size: int) -> Image.Image:
    base = Image.new("RGBA", (size, size))
    draw = ImageDraw.Draw(base)
    start = (20, 24, 80)
    end = (3, 6, 25)
    for y in range(size):
        t = y / (size - 1) if size > 1 else 0
        color = tuple(int(start[i] * (1 - t) + end[i] * t) for i in range(3))
        draw.line((0, y, size, y), fill=color)
    pad = int(size * 0.08)
    draw.ellipse((pad, pad, size - pad, size - pad), fill=(255, 255, 255, 20))
    font_size = max(int(size * 0.45), 48)
    font = load_font(font_size)
    bbox = draw.textbbox((0, 0), "ME", font=font)
    text_w = bbox[2] - bbox[0]
    text_h = bbox[3] - bbox[1]
    x = (size - text_w) / 2
    y = (size - text_h) / 2 - int(size * 0.02)
    draw.text((x + size * 0.01, y + size * 0.01), "ME", font=font, fill=(0, 0, 0, 140))
    draw.text((x, y), "ME", font=font, fill=(255, 255, 255, 255))
    return base


def save_icons(icon: Image.Image) -> None:
    densities = {
        "mdpi": 48,
        "hdpi": 72,
        "xhdpi": 96,
        "xxhdpi": 144,
        "xxxhdpi": 192,
    }
    for density, px in densities.items():
        folder = Path(f"android/app/src/main/res/mipmap-{density}")
        folder.mkdir(parents=True, exist_ok=True)
        resized = icon.resize((px, px), Image.LANCZOS)
        for name in ("ic_launcher.png", "ic_launcher_round.png", "ic_launcher_foreground.png"):
            resized.save(folder / name, format="PNG")


def main() -> None:
    icon = create_icon(512)
    icon.save("public/icon.png", format="PNG")
    save_icons(icon)


if __name__ == "__main__":
    main()
