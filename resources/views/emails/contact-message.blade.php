<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nouveau message de contact</title>
</head>
<body style="font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #0f172a;">
    <h2>Nouveau message de contact</h2>
    <p><strong>Nom :</strong> {{ $data['name'] }}</p>
    <p><strong>Email :</strong> {{ $data['email'] }}</p>
    <p><strong>Téléphone :</strong> {{ $data['phone'] ?? 'N/A' }}</p>
    <p><strong>Objet :</strong> {{ $data['subject'] ?: 'Sans objet' }}</p>
    <p><strong>Message :</strong></p>
    <p style="white-space: pre-line; line-height: 1.6;">{{ $data['message'] }}</p>
</body>
</html>
