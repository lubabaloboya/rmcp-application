<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documents Expiring Soon</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hello {{ $adminName }},</p>

    <p>
        The following RMCP documents are expiring within the next {{ $windowDays }} day(s).
        Please review and request updated documentation.
    </p>

    <table style="border-collapse: collapse; width: 100%; margin-top: 12px;">
        <thead>
            <tr>
                <th style="text-align: left; border-bottom: 1px solid #d1d5db; padding: 8px;">Client</th>
                <th style="text-align: left; border-bottom: 1px solid #d1d5db; padding: 8px;">Document</th>
                <th style="text-align: left; border-bottom: 1px solid #d1d5db; padding: 8px;">Expiry Date</th>
                <th style="text-align: left; border-bottom: 1px solid #d1d5db; padding: 8px;">Days Left</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documents as $item)
                <tr>
                    <td style="border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ $item['client_name'] }}</td>
                    <td style="border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ $item['document_type'] }}</td>
                    <td style="border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ $item['expiry_date'] ?? '-' }}</td>
                    <td style="border-bottom: 1px solid #e5e7eb; padding: 8px;">{{ $item['days_left'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 14px;">Regards,<br>RMCP System</p>
</body>
</html>
