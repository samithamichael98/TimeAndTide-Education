# Deployment Guide for nomehost

## Step-by-Step Deployment Instructions

### 1. Prepare Files for Upload

Before uploading, ensure you have:
- [ ] All project files ready
- [ ] Images added to `assets/images/` directory
- [ ] Email configuration updated in `process_contact.php`
- [ ] Contact information updated in `index.php`

### 2. Access nomehost Control Panel

1. **Login** to your nomehost account
2. **Navigate** to cPanel or hosting control panel
3. **Locate** File Manager option

### 3. Upload Files via cPanel File Manager

#### Option A: Using File Manager
1. **Open File Manager** in cPanel
2. **Navigate** to `public_html` directory
3. **Upload files** either by:
   - Drag and drop (if supported)
   - Using Upload button
4. **Extract** if uploaded as ZIP file

#### Option B: Using FTP Client
1. **Get FTP credentials** from nomehost
2. **Use FTP client** (FileZilla, WinSCP)
3. **Connect** to your server
4. **Upload files** to `public_html` directory

### 4. File Structure After Upload

Your `public_html` should look like:
```
public_html/
├── index.php
├── process_contact.php
├── .htaccess
├── README.md
├── DEPLOYMENT_GUIDE.md
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       ├── logo.png
│       ├── uk-flag.png
│       ├── canada-flag.png
│       ├── australia-flag.png
│       ├── italy-flag.png
│       └── latvia-flag.png
└── logs/ (will be auto-created)
```

### 5. Set File Permissions

Set proper permissions using File Manager:
- **Directories**: 755
- **PHP files**: 644
- **CSS/JS files**: 644
- **Images**: 644
- **.htaccess**: 644

### 6. Configure Email Settings

#### Update Email Configuration
Edit `process_contact.php` with your email details:

```php
$config = [
    'admin_email' => 'info@yourdomain.com',     // Change this
    'from_email' => 'noreply@yourdomain.com',   // Change this
    'site_name' => 'Time & Tide Education',
    'enable_file_logging' => true,
    'log_file' => 'logs/contact_submissions.log'
];
```

#### Test Email Functionality
1. **Create test script** `test_email.php`:
```php
<?php
$to = 'your-email@example.com';
$subject = 'Test Email from nomehost';
$message = 'This is a test email to verify mail functionality.';
$headers = 'From: noreply@yourdomain.com';

if(mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Email sending failed.";
}
?>
```
2. **Run test** by visiting `yourdomain.com/test_email.php`
3. **Delete test file** after verification

### 7. Update Domain/URL References

#### In index.php
Update any hardcoded URLs if present:
```html
<!-- Make sure all paths are relative -->
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/script.js"></script>
```

#### In .htaccess
Update domain if using redirects:
```apache
# Update this line if forcing www or non-www
RewriteCond %{HTTP_HOST} ^www\.yourdomain\.com$ [NC]
RewriteRule ^(.*)$ https://yourdomain.com%{REQUEST_URI} [R=301,L]
```

### 8. Test Website Functionality

#### Basic Functionality Test
- [ ] **Homepage loads** correctly
- [ ] **Navigation** works (smooth scrolling)
- [ ] **Mobile responsive** design displays properly
- [ ] **Images load** correctly
- [ ] **Animations** work smoothly

#### Contact Form Test
- [ ] **Form displays** correctly
- [ ] **Validation** works (try submitting empty form)
- [ ] **Submit form** with real data
- [ ] **Check email** arrives in inbox
- [ ] **Check auto-reply** is sent to user
- [ ] **Check logs** directory is created

### 9. SSL Certificate Setup (Optional but Recommended)

#### Check if SSL is Available
1. **Check cPanel** for SSL/TLS section
2. **Enable Let's Encrypt** if available
3. **Force HTTPS** by uncommenting in .htaccess:
```apache
# Uncomment these lines after SSL is active
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 10. Performance Optimization

#### Enable Compression (if not working)
Check if your hosting supports:
- **Gzip compression** (in .htaccess)
- **Browser caching** (in .htaccess)
- **CDN services** (if available)

#### Test Website Speed
Use tools like:
- Google PageSpeed Insights
- GTmetrix
- Pingdom

### 11. Troubleshooting Common Issues

#### Website Not Loading
1. **Check file permissions**
2. **Verify index.php is in public_html**
3. **Check error logs** in cPanel
4. **Contact nomehost support**

#### Images Not Displaying
1. **Check image file paths** (case-sensitive)
2. **Verify images uploaded** to correct directory
3. **Check file permissions** (644)
4. **Clear browser cache**

#### Contact Form Not Working
1. **Check PHP mail function** is enabled
2. **Verify email configuration**
3. **Check spam folder**
4. **Review error logs**
5. **Test with simple mail script**

#### CSS/JS Not Loading
1. **Check file paths** in HTML
2. **Verify files uploaded** correctly
3. **Clear browser cache**
4. **Check .htaccess** for blocking rules

### 12. Security Checklist

After deployment:
- [ ] **Remove test files** (test_email.php, etc.)
- [ ] **Verify .htaccess** is active
- [ ] **Check file permissions**
- [ ] **Test contact form** for XSS/SQL injection protection
- [ ] **Backup website** regularly

### 13. Regular Maintenance

#### Weekly Tasks
- [ ] **Check contact form** submissions in logs
- [ ] **Monitor email** delivery
- [ ] **Check for broken links**

#### Monthly Tasks
- [ ] **Backup website** files
- [ ] **Update contact information** if needed
- [ ] **Review performance** metrics
- [ ] **Check security** updates

### 14. Contact Information to Update

Don't forget to update these in `index.php`:
```html
<!-- Update these details -->
<p>Hill Street, Dehiwala, Sri Lanka</p>
<p>+94 777701206</p>
<p>+94 773215368</p>
<p>info@timeandtide.lk</p>
```

### 15. Analytics Setup (Optional)

Add Google Analytics:
1. **Get tracking code** from Google Analytics
2. **Add to index.php** before closing `</body>` tag:
```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_TRACKING_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_TRACKING_ID');
</script>
```

## Success Checklist

✅ **All files uploaded** to public_html  
✅ **File permissions** set correctly  
✅ **Email configuration** updated  
✅ **Website loads** without errors  
✅ **Contact form** works and sends emails  
✅ **Mobile responsiveness** verified  
✅ **Images display** correctly  
✅ **Navigation** functions properly  
✅ **SSL certificate** enabled (if available)  
✅ **Performance** optimized  

Your Time & Tide Education website is now live and ready to help students achieve their international education goals! 🎓✈️