<?php
// Mail template'leri

/**
 * Site durumu bildirimi için HTML template
 */
function getSiteNotificationTemplate($site_name, $site_url, $status, $timestamp) {
    $status_text = $status === 'up' ? 'ÇALIŞIYOR' : 'KESİNTİ';
    $status_color = $status === 'up' ? '#10B981' : '#EF4444';
    $status_bg = $status === 'up' ? '#ECFDF5' : '#FEF2F2';
    $status_border = $status === 'up' ? '#10B981' : '#EF4444';
    
    return "
    <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
    <html xmlns='http://www.w3.org/1999/xhtml'>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <meta name='viewport' content='width=device-width, initial-scale=1.0' />
        <title>Site Durumu Bildirimi</title>
        <!--[if mso]>
        <noscript>
            <xml>
                <o:OfficeDocumentSettings>
                    <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
            </xml>
        </noscript>
        <![endif]-->
    </head>
    <body style='margin: 0; padding: 0; background-color: #F9FAFB; font-family: Arial, sans-serif;'>
        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F9FAFB;'>
            <tr>
                <td align='center' style='padding: 20px 0;'>
                    <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='480' style='max-width: 480px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);'>
                        
                        <!-- Header -->
                        <tr>
                            <td style='background-color: #1F2937; padding: 24px; text-align: center; border-radius: 12px 12px 0 0;'>
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                    <tr>
                                        <td style='text-align: center;'>
                                            <div style='font-size: 18px; font-weight: bold; color: #FFFFFF; font-family: Arial, sans-serif;'>Uptime Monitor</div>
                                            <div style='font-size: 14px; color: #9CA3AF; margin-top: 4px; font-family: Arial, sans-serif;'>Site Durumu Bildirimi</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Main Content -->
                        <tr>
                            <td style='padding: 32px 24px;'>
                                
                                <!-- Status Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: {$status_bg}; border: 1px solid {$status_border}; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 24px; text-align: center;'>
                                            <div style='width: 12px; height: 12px; background-color: {$status_color}; border-radius: 50%; margin: 0 auto 12px;'></div>
                                            <div style='font-size: 16px; font-weight: bold; color: {$status_color}; font-family: Arial, sans-serif;'>{$status_text}</div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Site Info Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 20px;'>
                                            
                                            <!-- Site Name -->
                                            <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                <tr>
                                                    <td style='padding: 12px 0; border-bottom: 1px solid #E5E7EB;'>
                                                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                            <tr>
                                                                <td style='font-size: 14px; font-weight: 500; color: #6B7280; font-family: Arial, sans-serif; width: 80px;'>Site</td>
                                                                <td style='font-size: 14px; font-weight: bold; color: #111827; font-family: Arial, sans-serif; text-align: right;'>{$site_name}</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style='padding: 12px 0 0 0;'>
                                                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                            <tr>
                                                                <td style='font-size: 14px; font-weight: 500; color: #6B7280; font-family: Arial, sans-serif; width: 80px;'>URL</td>
                                                                <td style='font-size: 14px; font-weight: bold; color: #111827; font-family: Arial, sans-serif; text-align: right;'>
                                                                    <a href='{$site_url}' style='color: #3B82F6; text-decoration: none; word-break: break-all;'>{$site_url}</a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                            
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Timestamp Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 6px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 16px; text-align: center;'>
                                            <div style='font-size: 13px; color: #6B7280; font-family: monospace;'>{$timestamp}</div>
                                        </td>
                                    </tr>
                                </table>
                                
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #F9FAFB; border-top: 1px solid #E5E7EB; padding: 20px 24px; text-align: center; border-radius: 0 0 12px 12px;'>
                                <div style='font-size: 12px; color: #9CA3AF; line-height: 1.4; margin-bottom: 12px; font-family: Arial, sans-serif;'>
                                    Bu bildirim <strong>Uptime Monitor</strong> sistemi tarafından otomatik olarak gönderilmiştir.
                                </div>
                                <div style='font-size: 12px; font-family: Arial, sans-serif;'>
                                    <a href='#' style='color: #3B82F6; text-decoration: none; margin: 0 8px;'>Ayarları Değiştir</a>
                                    <a href='#' style='color: #3B82F6; text-decoration: none; margin: 0 8px;'>Bildirimleri Durdur</a>
                                </div>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

/**
 * Test mail için HTML template
 */
function getTestMailTemplate($timestamp) {
    return "
    <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
    <html xmlns='http://www.w3.org/1999/xhtml'>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <meta name='viewport' content='width=device-width, initial-scale=1.0' />
        <title>Test Mail</title>
        <!--[if mso]>
        <noscript>
            <xml>
                <o:OfficeDocumentSettings>
                    <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
            </xml>
        </noscript>
        <![endif]-->
    </head>
    <body style='margin: 0; padding: 0; background-color: #F9FAFB; font-family: Arial, sans-serif;'>
        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F9FAFB;'>
            <tr>
                <td align='center' style='padding: 20px 0;'>
                    <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='480' style='max-width: 480px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);'>
                        
                        <!-- Header -->
                        <tr>
                            <td style='background-color: #059669; padding: 24px; text-align: center; border-radius: 12px 12px 0 0;'>
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                    <tr>
                                        <td style='text-align: center;'>
                                            <div style='font-size: 18px; font-weight: bold; color: #FFFFFF; font-family: Arial, sans-serif;'>Uptime Monitor</div>
                                            <div style='font-size: 14px; color: #D1FAE5; margin-top: 4px; font-family: Arial, sans-serif;'>Test Mail Başarılı</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Main Content -->
                        <tr>
                            <td style='padding: 32px 24px; text-align: center;'>
                                
                                <!-- Success Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #ECFDF5; border: 1px solid #10B981; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 24px; text-align: center;'>
                                            <div style='width: 16px; height: 16px; background-color: #10B981; border-radius: 50%; margin: 0 auto 12px;'></div>
                                            <div style='font-size: 16px; font-weight: bold; color: #065F46; font-family: Arial, sans-serif;'>MAIL AYARLARI DOĞRU</div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Message Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 20px; text-align: left;'>
                                            <div style='font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 12px; font-family: Arial, sans-serif;'>
                                                <strong>Harika!</strong> Mail ayarlarınız doğru çalışıyor.
                                            </div>
                                            <div style='font-size: 14px; color: #374151; line-height: 1.6; font-family: Arial, sans-serif;'>
                                                Bu bir test mailidir ve SMTP ayarlarınızın başarıyla yapılandırıldığını gösterir.
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Timestamp Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 6px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 16px; text-align: center;'>
                                            <div style='font-size: 13px; color: #6B7280; font-family: monospace;'>{$timestamp}</div>
                                        </td>
                                    </tr>
                                </table>
                                
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #F9FAFB; border-top: 1px solid #E5E7EB; padding: 20px 24px; text-align: center; border-radius: 0 0 12px 12px;'>
                                <div style='font-size: 12px; color: #9CA3AF; font-family: Arial, sans-serif;'>
                                    <strong>Uptime Monitor</strong> - Site İzleme Sistemi
                                </div>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

/**
 * Manuel kontrol bildirimi için HTML template
 */
function getManualCheckTemplate($site_name, $site_url, $status, $timestamp, $response_time = null) {
    $status_text = $status === 'up' ? 'ÇALIŞIYOR' : 'KESİNTİ';
    $status_color = $status === 'up' ? '#10B981' : '#EF4444';
    $status_bg = $status === 'up' ? '#ECFDF5' : '#FEF2F2';
    $status_border = $status === 'up' ? '#10B981' : '#EF4444';
    
    return "
    <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
    <html xmlns='http://www.w3.org/1999/xhtml'>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <meta name='viewport' content='width=device-width, initial-scale=1.0' />
        <title>Manuel Kontrol Bildirimi</title>
        <!--[if mso]>
        <noscript>
            <xml>
                <o:OfficeDocumentSettings>
                    <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
            </xml>
        </noscript>
        <![endif]-->
    </head>
    <body style='margin: 0; padding: 0; background-color: #F9FAFB; font-family: Arial, sans-serif;'>
        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F9FAFB;'>
            <tr>
                <td align='center' style='padding: 20px 0;'>
                    <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='480' style='max-width: 480px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);'>
                        
                        <!-- Header -->
                        <tr>
                            <td style='background-color: #7C3AED; padding: 24px; text-align: center; border-radius: 12px 12px 0 0;'>
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                    <tr>
                                        <td style='text-align: center;'>
                                            <div style='font-size: 18px; font-weight: bold; color: #FFFFFF; font-family: Arial, sans-serif;'>Uptime Monitor</div>
                                            <div style='font-size: 14px; color: #C4B5FD; margin-top: 4px; font-family: Arial, sans-serif;'>Manuel Kontrol Sonucu</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Main Content -->
                        <tr>
                            <td style='padding: 32px 24px;'>
                                
                                <!-- Manual Check Badge -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F3E8FF; border: 1px solid #7C3AED; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 16px; text-align: center;'>
                                            <div style='font-size: 14px; font-weight: bold; color: #7C3AED; font-family: Arial, sans-serif;'>🔧 MANUEL KONTROL SONUCU</div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Status Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: {$status_bg}; border: 1px solid {$status_border}; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 24px; text-align: center;'>
                                            <div style='width: 12px; height: 12px; background-color: {$status_color}; border-radius: 50%; margin: 0 auto 12px;'></div>
                                            <div style='font-size: 16px; font-weight: bold; color: {$status_color}; font-family: Arial, sans-serif;'>{$status_text}</div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Site Info Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 20px;'>
                                            
                                            <!-- Site Name -->
                                            <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                <tr>
                                                    <td style='padding: 12px 0; border-bottom: 1px solid #E5E7EB;'>
                                                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                            <tr>
                                                                <td style='font-size: 14px; font-weight: 500; color: #6B7280; font-family: Arial, sans-serif; width: 80px;'>Site</td>
                                                                <td style='font-size: 14px; font-weight: bold; color: #111827; font-family: Arial, sans-serif; text-align: right;'>{$site_name}</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style='padding: 12px 0; border-bottom: 1px solid #E5E7EB;'>
                                                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                            <tr>
                                                                <td style='font-size: 14px; font-weight: 500; color: #6B7280; font-family: Arial, sans-serif; width: 80px;'>URL</td>
                                                                <td style='font-size: 14px; font-weight: bold; color: #111827; font-family: Arial, sans-serif; text-align: right;'>
                                                                    <a href='{$site_url}' style='color: #3B82F6; text-decoration: none; word-break: break-all;'>{$site_url}</a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>" . 
                                                ($response_time ? "
                                                <tr>
                                                    <td style='padding: 12px 0 0 0;'>
                                                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                            <tr>
                                                                <td style='font-size: 14px; font-weight: 500; color: #6B7280; font-family: Arial, sans-serif; width: 80px;'>Süre</td>
                                                                <td style='font-size: 14px; font-weight: bold; color: #111827; font-family: Arial, sans-serif; text-align: right;'>{$response_time}ms</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>" : "") . "
                                            </table>
                                            
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Timestamp Box -->
                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 6px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 16px; text-align: center;'>
                                            <div style='font-size: 13px; color: #6B7280; font-family: monospace;'>{$timestamp}</div>
                                        </td>
                                    </tr>
                                </table>
                                
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #F9FAFB; border-top: 1px solid #E5E7EB; padding: 20px 24px; text-align: center; border-radius: 0 0 12px 12px;'>
                                <div style='font-size: 12px; color: #9CA3AF; line-height: 1.4; margin-bottom: 12px; font-family: Arial, sans-serif;'>
                                    Bu bildirim <strong>Uptime Monitor</strong> sistemi tarafından manuel kontrol sonucu olarak gönderilmiştir.
                                </div>
                                <div style='font-size: 12px; font-family: Arial, sans-serif;'>
                                    <a href='#' style='color: #3B82F6; text-decoration: none; margin: 0 8px;'>Ayarları Değiştir</a>
                                    <a href='#' style='color: #3B82F6; text-decoration: none; margin: 0 8px;'>Bildirimleri Durdur</a>
                                </div>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
}
?>
