<?php

namespace App\Services\Telegram;

use App\Helpers\TypeNotify;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/* обратить внимание на класс ImagickDraw() в PHP */
class TelegramNotifierService
{
    public function __construct(
        private string $token = '',
        private int $id = 0,
    ) {
    }

    public function init(string $token, string $id)
    {
        $this->token = $token;
        $this->id = $id;
    }

    public function notify(TypeNotify $type, array $data)
    {
        match ($type) {
            TypeNotify::Text => $this->notifyMsg($data['text']),
            TypeNotify::Photo => $this->notifyPhoto($data['photo']),
        };
    }

    private function notifyMsg(string $msg)
    {
        $client = new Client();
        try {
            $client->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                RequestOptions::JSON => [
                    'chat_id' => $this->id,
                    'parse_mode' => 'html',
                    'text' => $msg,
                ]
            ]);
        } catch (Exception $e) {

            var_dump($e->getMessage());
        }
    }

//--- дальше все в разработке и тестировании

    private function notifyPhoto($photo)
    {
        $client = new Client();
        try {
            $client->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                RequestOptions::JSON => [
                    'chat_id' => $this->id,
                    'parse_mode' => 'html',
                    'photo' => $photo
                ]
            ]);
        } catch (Exception $e) {

            var_dump($e->getMessage());
        }
    }

    public function addTable(object $data,)
    {
        $code = null;
        $date = date('Y-m-d H:i:s');
        $status = 'OK';

        $len = 0;
        foreach ($data as $item) {
            $len_t = mb_strlen($item->task);
            if ($len_t > $len) $len = $len_t;
        }

        $len++;
        foreach ($data as $item) {
            $task = str_pad($item->task, $len);
            $code .= "<code>{$task} |</code><code> {$date} |</code><code> {$status}</code>\n";
        }
        return $code;
    }

    public function strToImg(string $text)
    {
        //header("Content-type: image/png; charset=utf-8");

        $text = "<table><tr><td>Ла ла ла</td></tr></table>";

        //--- константы
        $filename = storage_path("app/public/bg.png");
        $width = 400; //(strlen($text) * 9) + 20;
        $height = 30;

        $im = imagecreatetruecolor($width, $height);    // создание нового полноцветного изображения

        //--- создание цвета
        $white = imagecolorallocate($im, 255, 255, 255);
        $grey = imagecolorallocate($im, 128, 128, 128);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 399, 29, $white);   // рисование закрашенного прямоугольника

        //imagecolortransparent($im, $color);             // определяет цвет как прозрачный (для png)


        //imagestring($im, 5, 10, 5, $text, 0x535353);    // рисование строки текста горизонтально
        //$background = imagecreatefromjpeg($filename);   // создаёт новое изображение из файла или URL

        //$res = imagegd($im, $filename);                 // вывод GD-изображения в браузер или в файл
        //$res = imagepng($im, $filename);                // запись png
        //$res = imagejpeg($im, $filename);               // запись jpeg

        // Путь к ttf файлу шрифта
        //$font_file = './arial.ttf';
        //putenv('GDFONTPATH=' . realpath('.'));
        $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

        // Рисуем текст 'PHP Manual' шрифтом 13го размера
        $res = imagettftext($im, 12, 0, 1, 13, $black, $font, $text);
        $res = imagepng($im, $filename);                // запись png

        imagedestroy($im);                              // уничтожение изображения

        // // Merge background image and text image layers
        // imagecopymerge($background, $im, 15, 15, 0, 0, $width, $height, 100);
        // $output = imagecreatetruecolor($width, $height);
        // imagegd($output, storage_path("app/public/out.png"));

        // imagedestroy($output);
    }
}
