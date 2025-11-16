# Time & Tide Education Website

A modern, responsive one-page website for Time & Tide Education - International Student Visa Consultancy.

## Features

- ✨ Modern, clean design with white color scheme
- 📱 Fully responsive for all devices
- 🎭 Smooth animations and transitions
- 📧 Working contact form with PHP backend
- 🔒 Security optimizations for shared hosting
- ⚡ Performance optimized with caching and compression

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Fonts**: Google Fonts (Inter)
- **Icons**: Font Awesome 6
- **Hosting**: Optimized for shared hosting (nomehost)

## Project Structure

```
TimeandTide/
├── index.php                 # Main website file
├── process_contact.php       # Contact form handler
├── .htaccess                # Apache configuration
├── README.md                # This file
├── assets/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   ├── js/
│   │   └── script.js        # JavaScript functionality
│   └── images/              # Website images
│       ├── logo.png
│       ├── uk-flag.png
│       ├── canada-flag.png
│       ├── australia-flag.png
│       ├── italy-flag.png
│       └── latvia-flag.png
└── logs/                    # Contact form logs (auto-created)
```

## Installation & Deployment

### Local Development

1. **Clone or download** this project to your local machine
2. **Set up a local server** (XAMPP, WAMP, or MAMP)
3. **Place files** in your web server's document root
4. **Configure email settings** in `process_contact.php` if needed
5. **Test the website** by visiting `http://localhost/TimeandTide`

### nomehost Deployment

1. **Access your hosting control panel** (cPanel)
2. **Navigate to File Manager**
3. **Upload all files** to the `public_html` directory:
   ```
   public_html/
   ├── index.php
   ├── process_contact.php
   ├── .htaccess
   └── assets/
   ```
4. **Set permissions**:
   - Directories: 755
   - PHP files: 644
   - Log directory: 755 (will be auto-created)
5. **Test the contact form** to ensure email functionality works

### Email Configuration

Edit `process_contact.php` and update these settings:

```php
$config = [
    'admin_email' => 'info@timeandtide.lk',    // Your email
    'from_email' => 'noreply@yourdomain.com',  // From address
    'site_name' => 'Time & Tide Education',
    'enable_file_logging' => true,
    'log_file' => 'logs/contact_submissions.log'
];
```

## Required Images

Place these images in the `assets/images/` directory:

1. **logo.png** - Company logo (recommended: 200x200px)
2. **uk-flag.png** - UK flag icon (80x80px)
3. **canada-flag.png** - Canada flag icon (80x80px)
4. **australia-flag.png** - Australia flag icon (80x80px)
5. **italy-flag.png** - Italy flag icon (80x80px)
6. **latvia-flag.png** - Latvia flag icon (80x80px)

### Image Sources

You can download flag images from:
- [Flaticon](https://www.flaticon.com/free-icons/flag)
- [Icons8](https://icons8.com/icons/set/flag)
- [Country Flag Icons](https://github.com/lipis/flag-icons)

## Customization

### Colors

Update the CSS variables in `assets/css/style.css`:

```css
:root {
    --primary-color: #2563eb;     /* Main blue color */
    --secondary-color: #1e40af;   /* Darker blue */
    --accent-color: #3b82f6;      /* Light blue accent */
    /* ... other colors */
}
```

### Content

Edit the content directly in `index.php`:
- Company information
- Services offered
- Contact details
- Countries served

### Contact Form Fields

Modify form fields in both:
1. `index.php` (HTML form)
2. `process_contact.php` (validation rules)

## Browser Compatibility

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Features

- **Gzip compression** enabled via .htaccess
- **Browser caching** for static assets
- **Optimized CSS** with efficient selectors
- **Lazy loading** ready (can be implemented)
- **Minification ready** (can be implemented for production)

## Security Features

- 🛡️ Input validation and sanitization
- 🛡️ CSRF protection ready
- 🛡️ XSS prevention headers
- 🛡️ SQL injection protection
- 🛡️ File access restrictions
- 🛡️ Error log protection

## SEO Features

- 📊 Semantic HTML5 structure
- 📊 Meta tags for description and keywords
- 📊 Open Graph ready (can be implemented)
- 📊 Schema.org markup ready (can be implemented)
- 📊 Clean URLs

## Contact Form Features

- ✉️ HTML email templates
- ✉️ Auto-reply functionality
- ✉️ Form validation (client and server-side)
- ✉️ Submission logging
- ✉️ Spam protection ready
- ✉️ Mobile-friendly form design

## Troubleshooting

### Contact Form Not Working

1. **Check email settings** in hosting control panel
2. **Verify PHP mail() function** is enabled
3. **Check file permissions** on process_contact.php (644)
4. **Review error logs** in cPanel
5. **Test with simple mail script** first

### Images Not Loading

1. **Check file paths** in HTML
2. **Verify image files** are uploaded correctly
3. **Check file permissions** (644 for images)
4. **Clear browser cache**

### Styling Issues

1. **Check CSS file path** in HTML
2. **Verify CSS file** is uploaded
3. **Clear browser cache**
4. **Check for CSS conflicts**

## Support

For technical support:
- Check hosting provider documentation
- Review browser console for JavaScript errors
- Verify all files are uploaded correctly
- Ensure proper file permissions

## License

This project is created for Time & Tide Education. All rights reserved.

## Updates

- **Version 1.0** - Initial release with modern design and full functionality
- Mobile responsive design
- Contact form with PHP backend
- SEO and performance optimizations