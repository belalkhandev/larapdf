<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Belal\LaraPdf\Pdf loadView(string $view, array $data = [])
 * @method static \Belal\LaraPdf\Pdf loadHtml(string $html)
 * @method static \Belal\LaraPdf\Pdf paperSize(string $size)
 * @method static \Belal\LaraPdf\Pdf orientation(string $orientation)
 * @method static \Belal\LaraPdf\Pdf margins(int $top, int $right, int $bottom, int $left)
 * @method static \Belal\LaraPdf\Pdf headerView(string $view, array $data = [])
 * @method static \Belal\LaraPdf\Pdf footerView(string $view, array $data = [])
 * @method static \Belal\LaraPdf\Pdf font(string $name, string $path)
 * @method static \Belal\LaraPdf\Pdf withTailwind(?string $path = null)
 * @method static \Belal\LaraPdf\Pdf waitUntilNetworkIdle()
 * @method static \Belal\LaraPdf\Pdf timeout(int $seconds)
 * @method static \Illuminate\Http\Response download(string $filename = 'document.pdf')
 * @method static \Illuminate\Http\Response stream(string $filename = 'document.pdf')
 * @method static bool saveToDisk(string $path, ?string $disk = null)
 * @method static string base64()
 * @method static string inline()
 * @method static void queue(string $path, ?string $disk = null)
 *
 * @see \Belal\LaraPdf\Pdf
 */
class PDF extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'larapdf';
    }
}
