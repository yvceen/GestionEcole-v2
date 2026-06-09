# My Edu ZKBioTime Connector

Petit connecteur local à lancer sur le PC où ZKBioTime tourne.

Il lit les fichiers TXT générés par ZKBioTime dans `C:\ZKBioTime\files\temp\AutoExport`, transforme les pointages en JSON, puis les envoie vers My Edu.

## Configuration

1. Copiez `config.example.json` vers `config.json`.
2. Remplissez :
   - `api_url`: `https://myedu.school/api/integrations/zktime/attendance`
   - `token`: même valeur que `ZKTIME_CONNECTOR_TOKEN` dans `.env` Laravel sur le VPS.
   - `school_id`: ID de l'école dans My Edu.
   - `export_dir`: dossier AutoExport de ZKBioTime.

## Lancement manuel

```powershell
python tools\zktime-connector\zktime_connector.py --config tools\zktime-connector\config.json
```

## Notes

- Le connecteur garde un fichier `.zktime_state.json` pour éviter de renvoyer les mêmes pointages.
- Le backend My Edu filtre aussi les doublons avec `employee_code + date + heure + terminal_sn`.
- Gardez le token secret. Ne le publiez pas sur GitHub.
