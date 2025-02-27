<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\DB;
use App\Http\Helpers;

class MessageHelper {
    public static function HasMessages() {
        $account = Helpers\LoginHelper::GetAccount();
        return DB::table('Message')
            ->where('PersonID', '=', $account->ID)
            ->where('MessageRead', '=', 0)
            ->exists();
    }

    public static function CreateMessage($personID, $templateID, $data) {
        switch($templateID) {
            case 1:
                $message = MessageHelper::CreateMessageTemplateTestPositive($personID, $data);
                break;
            case 2:
                $message = MessageHelper::CreateMessageTemplateTestNegative($personID, $data);
                break;
            case 3:
                $message = MessageHelper::CreateMessageTemplateGroupZoneWarning($personID, $data);
                break;
            case 4:
                $message = MessageHelper::CreateMessageTemplateReportDue($personID, $data);
                break;
            case 5:
                $message = MessageHelper::CreateMessageTemplateAlertLevelChange($personID, $data);
                break;
        }

        return DB::table('Message')->insert($message);
    }

    private static function CreateMessageTemplateTestPositive($personID, $data) {
        $person = DB::table('Person')->find($personID);
        $template = DB::table('MessageTemplate')->find(1);
        $recommendations = DB::table('Recommendation')->get();

        $recommendString = '<ul>';
        foreach ($recommendations as $recommendation) {
            $recommendString .= '<li>' . $recommendation->Text . '</li>';
        }
        $recommendString .= '</ul>';

        $text = $template->Template;
        $text = str_replace('{PersonName}', $person->FirstName . ' ' . $person->LastName, $text);
        $text = str_replace('{PublicHealthCentreName}', $data['PublicHealthCentreName'], $text);
        $text = str_replace('{PCRTestDateOfTest}', $data['PCRTestDateOfTest'], $text);
        $text = str_replace('{url}', 'http://' . $_SERVER['HTTP_HOST'], $text);
        $text = str_replace('{Recommendations}', $recommendString, $text);

        return [
            'PersonID' => $personID,
            'TemplateID' => 1,
            'MessageRead' => 0,
            'Text' => $text,
            'DateTime' => date('Y-m-d')
        ];
    }

    private static function CreateMessageTemplateTestNegative($personID, $data) {
        $person = DB::table('Person')->find($personID);
        $template = DB::table('MessageTemplate')->find(2);

        $text = $template->Template;
        $text = str_replace('{PersonName}', $person->FirstName . ' ' . $person->LastName, $text);
        $text = str_replace('{PublicHealthCentreName}', $data['PublicHealthCentreName'], $text);
        $text = str_replace('{PCRTestDateOfTest}', $data['PCRTestDateOfTest'], $text);
        $text = str_replace('{url}', 'http://' . $_SERVER['HTTP_HOST'], $text);

        return [
            'PersonID' => $personID,
            'TemplateID' => 2,
            'MessageRead' => 0,
            'Text' => $text,
            'DateTime' => date('Y-m-d')
        ];
    }

    private static function CreateMessageTemplateGroupZoneWarning($personID, $data) {
        $person = DB::table('Person')->find($personID);
        $template = DB::table('MessageTemplate')->find(3);

        $text = $template->Template;
        $text = str_replace('{PersonName}', $person->FirstName . ' ' . $person->LastName, $text);

        return [
            'PersonID' => $personID,
            'TemplateID' => 3,
            'MessageRead' => 0,
            'Text' => $text,
            'DateTime' => date('Y-m-d')
        ];
    }

    private static function CreateMessageTemplateReportDue($personID, $data) {
        $person = DB::table('Person')->find($personID);
        $template = DB::table('MessageTemplate')->find(4);
        $recommendations = DB::table('Recommendation')->get();

        $recommendString = '<ul>';
        foreach ($recommendations as $recommendation) {
            $recommendString .= '<li>' . $recommendation->Text . '</li>';
        }
        $recommendString .= '</ul>';

        $text = $template->Template;
        $text = str_replace('{PersonName}', $person->FirstName . ' ' . $person->LastName, $text);
        $text = str_replace('{url}', 'http://' . $_SERVER['HTTP_HOST'], $text);
        $text = str_replace('{Recommendations}', $recommendString, $text);

        return [
            'PersonID' => $personID,
            'TemplateID' => 4,
            'MessageRead' => 0,
            'Text' => $text,
            'DateTime' => date('Y-m-d')
        ];
    }

    private static function CreateMessageTemplateAlertLevelChange($personID, $data) {
        $person = DB::table('Person')->find($personID);
        $template = DB::table('MessageTemplate')->find(5);
        $alert = DB::table('AlertLevel')->find($data['AlertLevelID']);

        $text = $template->Template;
        $text = str_replace('{PersonName}', $person->FirstName . ' ' . $person->LastName, $text);
        $text = str_replace('{RegionName}', $data['RegionName'], $text);
        $text = str_replace('{AlertLevelID}', $data['AlertLevelID'], $text);
        $text = str_replace('{AlertLevelName}', $alert->Name, $text);
        $text = str_replace('{AlertLevelDescription}', $alert->Description, $text);

        return [
            'PersonID' => $personID,
            'TemplateID' => 5,
            'MessageRead' => 0,
            'Text' => $text,
            'DateTime' => date('Y-m-d'),
            'NewAlertID' => $data['AlertLevelID'],
            'OldAlertID' => $data['OldAlertID']
        ];
    }
}