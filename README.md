# LaraPdf

Advanced Laravel PDF generation package built for the modern web. Pixel-perfect, Tailwind CSS ready, and complex script support (like Bengali) out of the box.

---

## Why LaraPdf?

Generating PDFs in Laravel has historically been a pain. Traditional libraries often struggle with:
- **Modern CSS**: Flexbox, Grid, and Tailwind CSS utility classes rarely work correctly.
- **Complex Scripts**: Bengali, Arabic, or Hindi fonts often render as gibberish or broken boxes.
- **Environment Discrepancies**: "It works on my machine but not on the server" due to missing system dependencies.

**LaraPdf** solves these by using a real Headless Chrome engine (via Puppeteer). What you see in your browser is exactly what you get in your PDF.

### The Experience
- ✨ **Developer Friendly**: A clean, fluent API that feels like native Laravel.
- 🎨 **Visual Excellence**: Full support for Tailwind CSS, background colors, and gradients.
- 🔡 **Font Mastery**: Seamless integration for Google Fonts and complex Unicode scripts.
- ⏳ **Smart Rendering**: Automatically waits for network idle to ensure all assets are loaded.
- 🚀 **Zero-Config**: Built-in command to install and manage Chrome within your project.

---


## Installation

You can install the package via composer:

```bash
composer require belalkhandev/larapdf
```

Publish the config file:

```bash
php artisan vendor:publish --tag=belalkhandev-larapdf-config
```


Install Puppeteer (required):

```bash
npm install puppeteer --save
```

## Basic Usage

```php
use Belal\LaraPdf\Facades\PDF;

PDF::loadView('invoices.template', ['invoice' => $invoice])
    ->download('invoice.pdf');
```

## Advanced Usage

```php
PDF::loadView('reports.annual', $data)
    ->paperSize('A4')
    ->orientation('landscape')
    ->margins(15, 15, 15, 15)
    ->withTailwind()
    ->font('Inter', resource_path('fonts/Inter.ttf'))
    ->googleFont('Inter')
    ->headerView('pdf.header', ['company' => $company])
    ->footerView('pdf.footer')
    ->waitUntilNetworkIdle()
    ->timeout(120)
    ->saveToDisk('reports/annual-2026.pdf', 's3');
```

### HTML Preview

For faster development, you can preview the rendered HTML directly in your browser without generating a PDF.

```php
PDF::loadView('invoice', $data)
    ->withTailwind()
    ->preview(); // Returns an Illuminate\Http\Response
```

Or just get the raw HTML string:

```php
$html = PDF::loadView('invoice', $data)->toHtml();
```

### Custom Fonts

LaraPdf supports both local font files and Google Fonts seamlessly:

```php
PDF::loadView('invoice', $data)
    ->googleFont('Inter') // Load 'Inter' from Google Fonts
    ->font('BrandFont', resource_path('fonts/brand.ttf')) // Load a local .ttf file
    ->stream();
```

> [!NOTE]
> When using `googleFont()`, the package fetches and inlines the font CSS for reliability in isolated Puppeteer contexts.

### Paper Size & Orientation

```php
// Standard size
PDF::loadView('invoice', $data)
    ->paperSize('a4') // a3, a4, a5, letter, etc.
    ->orientation('landscape')
    ->stream();

// Custom size (width, height, unit)
// Supports: mm, cm, in, px
PDF::loadView('invoice', $data)
    ->paperSize(100, 200, 'mm')
    ->stream();
```


### Margins

Set margins in millimeters (top, right, bottom, left):

```php
PDF::loadView('invoice', $data)
    ->margins(10, 10, 10, 10)
    ->stream();
```

## Header and Footer Support


LaraPdf makes it incredibly easy to add headers and footers to your PDFs.

### 1. Automatic Extraction (easiest)
Just use `<header>` and `<footer>` tags directly in your Blade view. LaraPdf will extract them and repeat them on every page automatically.

```html
<!-- in your blade view -->
<header>
    <div class="text-right">Page <span class="pageNumber"></span></div>
</header>
```

### 2. Manual Definition
You can also define headers and footers using the fluent API:

```php
PDF::loadView('reports.annual', $data)
    ->headerView('pdf.header', ['title' => 'Annual Report'])
    ->footerView('pdf.footer')
    ->stream();
```

> [!TIP]
> LaraPdf automatically adjusts your top and bottom margins to **35mm** when a header or footer is detected. It also includes built-in support for common Tailwind utility classes (`flex`, `justify-between`, `font-bold`, `text-green-600`, etc.) inside headers and footers for easy styling.

## Queue Support

```php
PDF::loadView('reports.heavy', $data)
    ->queue('reports/heavy.pdf', 's3');
```

## Troubleshooting

### "Could not find Chrome" error
This usually happens when Puppeteer cannot find the browser executable in its default cache directory.

#### 1. Automatic Install (Smart Detection)
Run the built-in command. It will automatically detect if you have a system Chrome installed and suggest using it to save 600MB:
```bash
php artisan larapdf:install-chrome
```
If not found, it will download a project-specific version. It is smart enough to skip the download if already installed.


#### 2. Specify Cache Path (Recommended for pnpm/Shared Hosting)
If you are using pnpm or a restricted environment, specify a custom cache path in your `.env`:
```env
PDF_PUPPETEER_CACHE_PATH=/var/www/your-project/storage/puppeteer
```
Then run the install command again.

#### 3. Use System Chrome
If you already have Chrome/Chromium installed on your system:
```env
PDF_CHROME_PATH=/usr/bin/google-chrome
```

## License


The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
