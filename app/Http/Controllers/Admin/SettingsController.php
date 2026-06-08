<?php

namespace App\Http\Controllers\Admin;

use DateTimeZone;
use App\Models\Setting;
use App\Models\Currency;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CashbackSetup;
use App\Models\Country;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $settings;
    public $user;

    public function __construct(Setting $settings)
    {
        $this->settings     = $settings;
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    // Setting
    public function index()
    {
        // if (is_null($this->user) || !$this->user->can('admin.settings.view')) {
        //         abort(403, 'Sorry !! You are Unauthorized.');
        // }
        $data['title'] = 'Settings';
        $data['settings'] = Setting::first();
        $data['payment_cashback'] = CashbackSetup::where('cashback_type', 'payment_cashback')->first();

        return view('admin.settings', $data);
    }

    // Update Setting
    public function store(Request $request)
    {
        DB::beginTransaction();

        // dd($request->all());
        try {

            $setting = Setting::first();

            if ($request->hasFile('site_logo')) {
                $file = $request->file('site_logo');
                $filename = uniqid().".".$file->getClientOriginalExtension();
                $file->move(public_path('uploads/logo'), $filename);
                $setting->site_logo = 'uploads/logo/'.$filename;
            }
             if ($request->hasFile('favicon')) {
                $file = $request->file('favicon');
                $filename = uniqid().".".$file->getClientOriginalExtension();
                $file->move(public_path('uploads/favicon'), $filename);
                $setting->favicon = 'uploads/favicon/'.$filename;
            }

            $setting->site_name                         = $request->site_name;
            // $setting->primary_color                     = $request->primary_color;
            // $setting->secondary_color                   = $request->secondary_color;
            // $setting->smtp_host                         = $request->smtp_host;
            // $setting->smtp_port                         = $request->smtp_port;
            // $setting->smtp_username                     = $request->smtp_username;
            // $setting->smtp_address                      = $request->smtp_address;

            // Global default materiality thresholds for new tenants

            $setting->email                              = $request->email;
            $setting->support_email                      = $request->support_email;
            $setting->phone_no                           = $request->phone_no;
            $setting->office_address                     = $request->office_address;
            $setting->seo_meta_title                     = $request->seo_meta_title;
            $setting->seo_meta_description               = $request->seo_meta_description;
            $setting->seo_keywords                       = $request->seo_keywords;
            $setting->twitter_url                        = $request->twitter_url;
            $setting->linkedin_url                       = $request->linkedin_url;
            $setting->save();


            DB::commit();

            return redirect()->back()
                ->with('notify', [['success', 'Settings updated successfully!']]);

        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return redirect()->back()
                ->with('notify', [['error', 'Something went wrong. Please try again']]);
        }
    }

    public function cashbackUpdate(Request $request)
    {
        $request->validate([
            'user_cashback_rate' => 'required|numeric|min:0|max:100',
        ]);

        $cashbackSetup = CashbackSetup::where('cashback_type', 'payment_cashback')->first();
        $cashbackSetup->percentage = $request->user_cashback_rate;
        $cashbackSetup->save();

        return back()->with('success', 'Cashback updated successfully!');
    }

}
