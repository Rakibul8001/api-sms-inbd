<?php

namespace App\Core\SmsAdminSettings;

use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Config\Repository;
use App\Core\SmsAdminSettings\SmsAdminSettings;

class SmsAdminSettingsDetails implements SmsAdminSettings
{

    /**
     * Consume configuration 
     *
     * @var Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Handle file
     *
     * @var Illumintate\Support\Facades\File
     */
    protected $file;

    /**
     * Service instantiate 
     *
     * @param Repository $config
     * @param File $file
     */
    public function __construct(
        Repository $config,
        File $file
    )
    {
        $this->config = $config;

        $this->file = $file;
    }


    /**
     * Sms Admin Parameters & settings
     *
     * @return void
     */
    public function showSmsAdminSettings()
    {
        
        $config = $this->config['smsadminconfig'];

        if (empty($config)) {

            $config = [
                'site_information' => [
                    'site_name' => 'SMS Portal',
                    'site_slogan' => 'SMS Service Provider',
                    'address' => 'House: 43, Road: 11, Block: E, Niketon, Gulshan sdf saf sdf',
                    'email' => 'info@smsinbd.com',
                    'order_email' => 'info@smsinbd.com',
                    'email_from' => 'webmaster@smsinbd.com',
                    'contact_phone' => '01716187302',
                    ],
                'third_party_settings' => [
                    'fb_link' => 'https://facebook.com',
                    'twitter_link' => 'https://twitter.com',
                    'linkedin_link' => 'https://linkedin.com',
                    'recaptcha_site_key' => '6LegMloUAAAAAE3xeQqo-bqfndIwRGDdGe9v6E-C',
                    'about_site' => 'SMS Provider',
                    ],
                'site_parameters' => [
                    'under_maintenence' => 'y',
                    'maintenence_messsage' => 'This Page is currently UNDER MAINTENANCE, Please Wait....',
                    'max_audio_file_size' => '10485760',
                    'max_text_sms_limit' => '100000',
                    'max_voice_sms_limit' => '5000',
                    'text_limit_campaing' => '20000',
                    'voice_limit_campaing' => '500',
                    'ssl_comm_user' => 'smsinbd001live',
                    'ssl_comm_password' => '5BB4B235B706324870',
                    ],
            ];
            return $config;
        }
        return $config;
    }

    public function smsAdminSettingsUpdate($data)
    { 
        if (! is_array($data))
        {
            return response()->json(['errmsg' => "data must be an array, given {gettype($data)}"]);
        }

        try{
            $array = [];

            $array = [
                    'site_information' => [
                        'site_name' => $data['site_name'],
                        'site_slogan' => $data['site_slogan'],
                        'address' => $data['address'],
                        'email' => $data['email'],
                        'order_email' => $data['order_email'],
                        'email_from' => $data['email_from'],
                        'contact_phone' => $data['contact_phone'],
            
                    ],
            
                    'third_party_settings' => [
                        'fb_link' => $data['fb_link'],
                        'twitter_link' => $data['twitter_link'],
                        'linkedin_link' => $data['linkedin_link'],
                        'recaptcha_site_key' => $data['recaptcha_site_key'],
                        'about_site' => $data['about_site']
                    ],
            
                    'site_parameters' => [
                        'under_maintenence' => $data['under_maintenence'],
                        'maintenence_messsage' => $data['maintenence_messsage'],
                        'max_audio_file_size' => $data['max_audio_file_size'],
                        'max_text_sms_limit' => $data['max_text_sms_limit'],
                        'max_voice_sms_limit' => $data['max_voice_sms_limit'],
                        'text_limit_campaing' => $data['text_limit_campaing'],
                        'voice_limit_campaing' => $data['voice_limit_campaing'],
                        'ssl_comm_user' => $data['ssl_comm_user'],
                        'ssl_comm_password' => $data['ssl_comm_password']
                    ]
                ];

            $data = var_export($array,1);

            if ($this->file::exists(base_path() . '/config/smsadminconfig.php'))
            {

                if($this->file::put(base_path() . '/config/smsadminconfig.php', "<?php \n\r return $data;")) {
                    
                    return response()->json(['msg' =>'Settings successfully updated'],200);
                }

            } else {
                
                file_put_contents(base_path() . '/config/smsadminconfig.php',"<?php \n\r return $data;");

                return response(['msg' =>'Settings successfully updated'],200);
            }
        } catch(\Exception $e) {

            return response()->json(['errmsg' => $e->getMessage()],406);
        
        }

    }
}