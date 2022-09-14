<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel; 
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use stdClass;
class TranslateJson 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
     
    public function Translate($langFrom = null, $langTo = null, $contentFile = null){

        $translate = new stdClass();
		$init      = 0;
		$limit     = 200;

		foreach ($contentFile as $property => $argument) { 
			
            if ($init <= $limit) { 
                $encoded_text = urlencode($argument);
				$url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl='.$langFrom.'&tl='.$langTo.'&dt=t&q='.$encoded_text;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
				curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
				$output = curl_exec($ch);
				curl_close($ch); 

				$response_a = json_decode($output);
				foreach ($response_a[0] as $text_block) {
					$translate->$argument = $text_block[0];
				}
            }
			$init ++;
		}
		
		// Creamos el archivo 
		$archivo = fopen(public_path('translate/'.$langTo.'.json'),'w');  
		// Rellenamos y verificamos codificacion ANSI
		$trans   = $this->Utf8_ansi(json_encode($translate));
		fwrite($archivo,$trans);
		// Cerramos el archivo
		fclose($archivo);  

        
        if (file_exists(public_path('translate/'.$langTo.'.json'))) {
           return true;
        }else{ 
            return false;
        }
    }

    /**
     * 
     * Conversor de caracteres ansi
     * 
     */
    public static function Utf8_ansi($vl='') {

		$utf8_ansi2 = array(
			"\u00c0" =>"À",
			"\u00c1" =>"Á",
			"\u00c2" =>"Â",
			"\u00c3" =>"Ã",
			"\u00c4" =>"Ä",
			"\u00c5" =>"Å",
			"\u00c6" =>"Æ",
			"\u00c7" =>"Ç",
			"\u00c8" =>"È",
			"\u00c9" =>"É",
			"\u00ca" =>"Ê",
			"\u00cb" =>"Ë",
			"\u00cc" =>"Ì",
			"\u00cd" =>"Í",
			"\u00ce" =>"Î",
			"\u00cf" =>"Ï",
			"\u00d1" =>"Ñ",
			"\u00d2" =>"Ò",
			"\u00d3" =>"Ó",
			"\u00d4" =>"Ô",
			"\u00d5" =>"Õ",
			"\u00d6" =>"Ö",
			"\u00d8" =>"Ø",
			"\u00d9" =>"Ù",
			"\u00da" =>"Ú",
			"\u00db" =>"Û",
			"\u00dc" =>"Ü",
			"\u00dd" =>"Ý",
			"\u00df" =>"ß",
			"\u00e0" =>"à",
			"\u00e1" =>"á",
			"\u00e2" =>"â",
			"\u00e3" =>"ã",
			"\u00e4" =>"ä",
			"\u00e5" =>"å",
			"\u00e6" =>"æ",
			"\u00e7" =>"ç",
			"\u00e8" =>"è",
			"\u00e9" =>"é",
			"\u00ea" =>"ê",
			"\u00eb" =>"ë",
			"\u00ec" =>"ì",
			"\u00ed" =>"í",
			"\u00ee" =>"î",
			"\u00ef" =>"ï",
			"\u00f0" =>"ð",
			"\u00f1" =>"ñ",
			"\u00f2" =>"ò",
			"\u00f3" =>"ó",
			"\u00f4" =>"ô",
			"\u00f5" =>"õ",
			"\u00f6" =>"ö",
			"\u00f8" =>"ø",
			"\u00f9" =>"ù",
			"\u00fa" =>"ú",
			"\u00fb" =>"û",
			"\u00fc" =>"ü",
			"\u00fd" =>"ý",
			"\u00ff" =>"ÿ",
			"\u00bf" =>"¿",
            "\u00a1"=>"¡"
		); 

		return strtr($vl, $utf8_ansi2);       
	}
}
