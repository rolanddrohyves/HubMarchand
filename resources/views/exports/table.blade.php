<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}h1{font-size:18px;color:#0e7490}table{width:100%;border-collapse:collapse}th,td{border:1px solid #cbd5e1;padding:6px;text-align:left}th{background:#e2e8f0}</style></head>
<body><h1>{{ $title }}</h1><table><thead><tr>@foreach ($rows[0] ?? [] as $cell)<th>{{ $cell }}</th>@endforeach</tr></thead><tbody>@foreach (array_slice($rows, 1) as $row)<tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></body>
</html>
