<?php

namespace App\Http\Controllers\branchadmin;

use App\EmployeeMeeting;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Validator;

class JobController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    


public function meetingCallView($tab_id,$job_id,$id)
{
    $employee= Employee::where('id',$id)->first();
    if($employee) {
        $datas['employee'] = $employee;
        $datas['job_id']=$job_id;
        $datas['tab_id']=$tab_id;

        return view('branchadmin.jobs.meetingform')->with('datas', $datas);
    } else {

        \Session::flash('alert-danger','You choosed wrong Data');
        return redirect()->back();
    }
}


public function meetingView(Request $request)
{
    $v= Validator::make($request->all(),
        [
            'topic' => 'required|min:5',
            'password' => 'required|min:6|max:10',
        ]);
    if($v->fails()) {
        return redirect()->back()->withErrors($v)
            ->withInput();
    } else {
        $employee= Employee::where('id',$request->id)->first();
        $start_time=$request->start_date.'T'.$request->start_time.':00Z';
//        get token from function
        $token=$this->getZoomToken();
        if($employee) {
    //        zoom api
            $curl = curl_init();
            $body = json_encode(array(
                'topic'=> $request->topic,
                'type'=> '2',
                'start_time'=> $start_time,
                'duration'=> '40',
                'schedule_for'=> 'depeshkhatiwada@gmail.com',
                'timezone'=> 'Asia/Kathmandu',
                'password'=> $request->password,
                'agenda'=> $request->topic,
                'settings' => array(
                    'host_video'=> 'true',
                    'participant_video'=> 'true',
                    'cn_meeting'=> 'false',
                    'in_meeting'=> 'true',
                    'join_before_host'=> 'true',
                    'mute_upon_entry'=> 'true',
                    'watermark'=> 'false',
                    'use_pmi'=> 'false',
                    'approval_type'=> '0',
                    'registration_type'=> '1',
                    'audio'=> 'both',
                    'auto_recording'=> 'none',
                    'enforce_login'=> 'false',
                    'registrants_email_notification'=> 'true'
                )
            ), true);
            curl_setopt_array($curl, array(
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer $token",
                    "content-type: application/json"
                ),
                CURLOPT_URL => "https://api.zoom.us/v2/users/depeshkhatiwada@gmail.com/meetings",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,

            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $resp = json_decode($response);
//            dd($resp);
                try {
                    $email='deepaacee@gmail.com';
                    $staff = $employee->firstname;
                    //dd($email);
                    $val= [
                        'data' => $employee ,
                        'api' => $resp ,
                    ];
                    Mail::send('mail.zoom-meeting', $val, function ($message) use($email,$staff) {
                        $message->to($email, $staff);
                        $message->subject('Zoom Meeting Invitation');
                    });
                } catch (\Exception $e) {
                    dd($e);
                }
//                saving details to database
                $employeeMeeting=EmployeeMeeting::create([
                    'tab_id' => $request->tab_id,
                    'job_id' => $request->job_id,
                    'employee_id' => json_encode($employee->id),
                    'topic' => $resp->topic,
                    'start_time' => $resp->start_time,
                    'zoom_id' => $resp->id,
                    'zoom_password' => $resp->password,
                    'zoom_url'=> $resp->join_url
                ]);
                if($employeeMeeting)
                {
                    \Session::flash('alert-success','You Successfully invite application for zoom meeting with id: '.$resp->id);
                    return redirect('branchadmin/jobs');
                } else {

                    \Session::flash('alert-danger','Something Went Wrong on Saving Data');
                    return redirect('branchadmin/jobs');
                }
            }
        } else {

            \Session::flash('alert-danger','You choosed wrong Data');
            return redirect()->back();
        }
    }

}

public function groupMeetingView(Request $request)
{
//    dd($request);
    $v= Validator::make($request->all(),
        [
            'job_id'=>'required|integer',
        ]);
    if($v->fails())
    {
        \Session::flash('alert-danger','Sorry we did not found the job.');
        return redirect()->back();
    }
    if (count($request->employee_id) > 0) {
        $datas['employee_id']= $request->employee_id;
        $datas['job_id']= $request->job_id;
        $datas['tab_id']=$request->tab_id;
//        dd($datas['employee_id']);
        if($datas) {
            return view('branchadmin.jobs.groupmeetingform')->with('datas', $datas);

        } else {
            \Session::flash('alert-danger','You choosed wrong Data');
            return redirect()->back();
        }
    } else{
        \Session::flash('alert-danger','Sorry you did not select any application.');
        return redirect()->back();
    }
}

public function callGroupMeeting(Request $request)
{
    $v= Validator::make($request->all(),
        [
            'topic' => 'required|min:5',
            'password' => 'required|min:6|max:10',
        ]);
    if($v->fails()) {
        return redirect()->back()->withErrors($v)
            ->withInput();
    }
    if (count($request->id) > 0) {
        $start_time=$request->start_date.'T'.$request->start_time.':00Z';
//        get token from function
        $token=$this->getZoomToken();
//        zoom api
        $curl = curl_init();
        $body = json_encode(array(
            'topic'=> $request->topic,
            'type'=> '2',
            'start_time'=> $start_time,
            'duration'=> '40',
            'schedule_for'=> 'depeshkhatiwada@gmail.com',
            'timezone'=> 'Asia/Kathmandu',
            'password'=> $request->password,
            'agenda'=> $request->topic,
            'settings' => array(
                'host_video'=> 'true',
                'participant_video'=> 'true',
                'cn_meeting'=> 'false',
                'in_meeting'=> 'true',
                'join_before_host'=> 'true',
                'mute_upon_entry'=> 'true',
                'watermark'=> 'false',
                'use_pmi'=> 'false',
                'approval_type'=> '0',
                'registration_type'=> '1',
                'audio'=> 'both',
                'auto_recording'=> 'none',
                'enforce_login'=> 'false',
                'registrants_email_notification'=> 'true'
            )
        ), true);
        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $token",
                "content-type: application/json"
            ),
            CURLOPT_URL => "https://api.zoom.us/v2/users/depeshkhatiwada@gmail.com/meetings",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,

        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "CURL Error #:" . $err;
        } else {
            $resp = json_decode($response);
            foreach ($request->id as $value) {
                $employee = Employee::where('id', $value)->first();
                // sending mail
                try {
                    $email='deepaacee@gmail.com';
                    $staff = $employee->firstname;
                    //dd($email);
                    $val= [
                        'data' => $employee ,
                        'api' => $resp ,
                    ];
                    Mail::send('mail.zoom-meeting', $val, function ($message) use($email,$staff) {
                        $message->to($email, $staff);
                        $message->subject('Zoom Meeting Invitation');
                    });
                } catch (\Exception $e) {
                    dd($e);
                }
            }
//          saving details to database
            $employeeMeeting=EmployeeMeeting::create([
                'tab_id' => $request->tab_id,
                'job_id' => $request->job_id,
                'employee_id' => json_encode($request->id),
                'topic' => $resp->topic,
                'start_time' => $resp->start_time,
                'zoom_id' => $resp->id,
                'zoom_password' => $resp->password,
                'zoom_url'=> $resp->join_url
            ]);
            if($employeeMeeting)
            {
                \Session::flash('alert-success','You Successfully invite application for zoom meeting with id: '.$resp->id);
                return redirect('branchadmin/jobs');
            } else {

                \Session::flash('alert-danger','Something Went Wrong on Saving Data');
                return redirect('branchadmin/jobs');
            }
        }
    } else{
        \Session::flash('alert-danger','Sorry you did not select any application.');
        return redirect()->back();
    }
}


protected function getZoomToken()
{
//        get refresh_token from db
    $refresh_token=DB::table('zoom_api')->where('id', '=', 1)->first()->refresh_token;
    $token_url="https://zoom.us/oauth/token?grant_type=refresh_token&refresh_token=".$refresh_token;
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_HTTPHEADER => array(
            "authorization: Basic dVFRdWJJZk9UOGVOb19rcUdTNFZRZzpCZXZaZjlTQnEyUnMyS3hCYktkcmZ2bTJqcXp2NXdTdw==",
        ),
        CURLOPT_URL => $token_url,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_CUSTOMREQUEST => "POST",
    ));

    $data = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($data);
//        update refresh_token to db
    DB::table('zoom_api')->where('id', '=', 1)->update([
        'refresh_token' => $result->refresh_token,
    ]);
//        return  access_token
    return $result->access_token;
}





}

