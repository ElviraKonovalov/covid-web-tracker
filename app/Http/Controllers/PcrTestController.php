<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers;
use Illuminate\Support\Facades\DB;

class PcrTestController extends Controller {
    public function index() {
        return view('/pcrEntry', [
            'alerts' => []
        ]);
    }

    public function submit() {
        $alerts = [];
        $account = Helpers\LoginHelper::GetAccount();
        $person = DB::table('Person')->where('MedicareID', '=', $_POST['medicareID'])->first();
        $contract = DB::table('EmploymentContract')
            ->where('PublicHealthWorkerID', '=', $account->WorkerID)
            ->whereNull('EndDate')
            ->first();
        if ($contract == null) {
            $contract = DB::table('EmploymentContract')
                ->where('PublicHealthWorkerID', '=', $account->WorkerID)
                ->orderByDesc('EndDate')
                ->first();
        }

        if ($person == null) {
            array_push($alerts, [
                'type' => 'warning',
                'text' => 'Medicare ID not found!'
            ]);
        } else {
            $success = false;
            try {
                DB::table('PCRTest')->insert([
                    'PersonID' => $person->ID,
                    'DateOfTest' => $_POST['date'],
                    'PublicHealthCentreID' => $contract->PublicHealthCentreID,
                    'PublicHealthWorkerID' => $account->WorkerID,
                    'Result' => $_POST['result'] ?? '',
                    'DateOfResult' => date('Y-m-d')
                ]);
                array_push($alerts, [
                    'type' => 'success',
                    'text' => "Result saved successfully"
                ]);
                $success = true;
            } catch(\Illuminate\Database\QueryException $ex) {
                $message = $ex->getMessage();
                array_push($alerts, [
                    'type' => 'danger',
                    'text' => "Query exception: $message"
                ]);
            }
            // Send messages to the patient and, if necessary, their group zone
            if ($success) {
                $centre = DB::table('PublicHealthCentre')->find($contract->PublicHealthCentreID);
                if ($_POST['result'] == '1') {
                    // Alert that the test came back positive
                    Helpers\MessageHelper::CreateMessage($person->ID, 1, [
                        'PublicHealthCentreName' => $centre->Name,
                        'PCRTestDateOfTest' => $_POST['date']
                    ]);

                    // Send the message for symptom history
                    Helpers\MessageHelper::CreateMessage($person->ID, 4, []);

                    // Alert all members of the groupzone
                    $zones = DB::table('GroupZoneMembership')
                        ->where('PersonID', '=', $person->ID)
                        ->select('GroupZoneID');

                    $people = DB::table('Person')
                        ->join('GroupZoneMembership', 'GroupZoneMembership.PersonID', '=', 'Person.ID')
                        ->joinSub($zones, 'zones', 'GroupZoneMembership.GroupZoneID', '=', 'zones.GroupZoneID')
                        ->where('Person.ID', '<>', $person->ID)
                        ->select('Person.ID')
                        ->distinct()
                        ->get();

                    foreach ($people as $atRisk) {
                        Helpers\MessageHelper::CreateMessage($atRisk->ID, 3, []);
                    }
                } else {
                    // Alert that the test came back negative
                    Helpers\MessageHelper::CreateMessage($person->ID, 2, [
                        'PublicHealthCentreName' => $centre->Name,
                        'PCRTestDateOfTest' => $_POST['date']
                    ]);
                }
            }
        }
        return view('/pcrEntry', [
            'alerts' => $alerts
        ]);
    }
}