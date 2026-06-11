<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Khoi phuc mat khau TDC</title>
</head>
<body style="margin:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e4e9f2;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#003087;color:#ffffff;padding:22px 26px;">
                            <h1 style="margin:0;font-size:22px;line-height:1.35;">Khoi phuc mat khau TDC</h1>
                            <p style="margin:8px 0 0;color:#dbe7ff;font-size:14px;">He thong quan ly san pham sinh vien</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px;">
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Xin chao {{ $user->name }},</p>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Quan tri vien da cap mat khau tam thoi cho tai khoan cua ban.
                                Vui long dang nhap bang mat khau ben duoi.
                            </p>

                            <div style="margin:20px 0;padding:16px;border-radius:10px;background:#f0f5ff;border:1px solid #cddcff;">
                                <p style="margin:0 0 6px;color:#526071;font-size:13px;">Mat khau tam thoi</p>
                                <p style="margin:0;font-size:24px;font-weight:700;letter-spacing:1px;color:#003087;">{{ $temporaryPassword }}</p>
                            </div>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Sau khi dang nhap, hay vao trang ca nhan hoac du an cua ban de doi lai mat khau moi.
                            </p>

                            <a href="{{ $loginUrl }}" style="display:inline-block;background:#003087;color:#ffffff;text-decoration:none;font-weight:700;border-radius:10px;padding:12px 18px;">
                                Dang nhap he thong
                            </a>

                            <p style="margin:22px 0 0;color:#64748b;font-size:13px;line-height:1.6;">
                                Neu ban khong yeu cau khoi phuc mat khau, vui long lien he quan tri vien de duoc ho tro.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
