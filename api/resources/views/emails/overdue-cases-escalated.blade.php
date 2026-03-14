<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Overdue Cases Escalated</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
  <p>Hello {{ $adminName }},</p>
  <p>The following RMCP cases have exceeded their SLA and were escalated.</p>
  <table style="border-collapse: collapse; width: 100%; margin-top: 12px;">
    <thead>
      <tr>
        <th style="text-align:left; border-bottom:1px solid #d1d5db; padding:8px;">Case #</th>
        <th style="text-align:left; border-bottom:1px solid #d1d5db; padding:8px;">Title</th>
        <th style="text-align:left; border-bottom:1px solid #d1d5db; padding:8px;">Client</th>
        <th style="text-align:left; border-bottom:1px solid #d1d5db; padding:8px;">SLA Due</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($cases as $item)
        <tr>
          <td style="border-bottom:1px solid #e5e7eb; padding:8px;">{{ $item['case_number'] }}</td>
          <td style="border-bottom:1px solid #e5e7eb; padding:8px;">{{ $item['title'] }}</td>
          <td style="border-bottom:1px solid #e5e7eb; padding:8px;">{{ $item['client_name'] }}</td>
          <td style="border-bottom:1px solid #e5e7eb; padding:8px;">{{ $item['sla_due_at'] ?? '-' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <p style="margin-top: 14px;">Regards,<br>RMCP System</p>
</body>
</html>
