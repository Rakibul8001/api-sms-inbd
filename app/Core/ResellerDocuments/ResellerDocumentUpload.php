<?php

namespace App\Core\ResellerDocuments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Core\ResellerDocuments\ResellerDocumentsInterface;
use App\Http\Resources\ResellerResource;
use App\Reseller;
use App\ResellerDocument;
use Illuminate\Support\Facades\Auth;

class ResellerDocumentUpload implements ResellerDocumentsInterface
{

    /**
     * National ID document name
     *
     * @var string
     */
    protected $nationalid;

    /**
     * Application document name
     *
     * @var string
     */
    protected $application;


    /**
     * User PP size photo name
     *
     * @var string
     */
    protected $custppphoto;

    /**
     * Tradelicence document name
     *
     * @var string
     */
    protected $tradelicence;

    public function __construct()
    {
        
    }
    /**
     * Add NID Copy
     *
     * @param Request $request
     * @return void
     */
    public function addNid(Request $request)
    {
        if($request->hasFile('nationalid')){

            //$request->nationalid->getClientOriginalName();

            if (empty($request->userid))
            {
                $userid = Auth::guard('reseller')->user()->id;
            } else {
                $userid = $request->userid;
            }

            $filename = date("YmdHis").$userid.".".$request->nationalid->getClientOriginalExtension();

            $path = "reseller/nid/";

            $img = $request->nationalid->move($path, $filename );

            $this->nationalid = $filename;

            //$request->nationalid
            //        ->storeAs('public/nid',$filename);

        }

        return $this;
    }


    /**
     * Add Application Copy
     *
     * @param Request $request
     * @return void
     */
    public function addApplication(Request $request)
    {
        if($request->hasFile('application')){

            //$request->application->getClientOriginalName();

            if (empty($request->userid))
            {
                $userid = Auth::guard('reseller')->user()->id;
            } else {
                $userid = $request->userid;
            }

            $filename = date("YmdHis").$userid.".".$request->application->getClientOriginalExtension();

            $path = "reseller/applications/";

            $img = $request->application->move($path, $filename );

            $this->application = $filename;

            //$request->application
            //        ->storeAs('public/applications',$filename);
        }

        return $this;
    }

    /**
     * Add Custom PP Photo
     *
     * @param Request $request
     * @return void
     */
    public function addCustomPhoto(Request $request)
    {
        
        if($request->hasFile('custppphoto')){

            //$request->custppphoto->getClientOriginalName();

            if (empty($request->userid))
            {
                $userid = Auth::guard('reseller')->user()->id;
            } else {
                $userid = $request->userid;
            }

            $filename = date("YmdHis").$userid.".".$request->custppphoto->getClientOriginalExtension();

            $path = "reseller/clientphoto/";

            $img = $request->custppphoto->move($path, $filename );

            $this->custppphoto = $filename;

            //$request->custppphoto
            //        ->storeAs('public/clientphoto',$filename);
        }

        return $this;
    }

    /**
     * Add Trade Licence Copy
     *
     * @param Request $request
     * @return void
     */
    public function addTradeLicence(Request $request)
    {
        if($request->hasFile('tradelicence')){

            //$request->tradelicence->getClientOriginalName();

            if (empty($request->userid))
            {
                $userid = Auth::guard('reseller')->user()->id;
            } else {
                $userid = $request->userid;
            }

            $path = "reseller/tradelicence/";

            $filename = date("YmdHis").$userid.".".$request->tradelicence->getClientOriginalExtension();

            $img = $request->tradelicence->move($path, $filename );

            $this->tradelicence = $filename;

            //IF server symlink is active
            //$request->tradelicence
            //        ->storeAs('public/tradelicence',$filename);
        }

        return $this;
    }

    /**
     * Submit User Documents to the database
     *
     * @param Request $request
     * @return void
     */
    public function addUserDocuments(Request $request)
    {
        if ($request->has('nationalid')) {
            if (empty($request->userid))
            {
                
                $userid = Auth::guard('web')->user()->id;

                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/nid/'.$documents->nid));
                    //UserDocument::where('user_id', $userid)->delete();
                }

            } else {
                $userid = $request->userid;
                
                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/nid/'.$documents->nid));
                    //UserDocument::where('user_id', $request->user()->id)->delete();
                }
            }
        } 
        
        if ($request->has('application')) {
            if (empty($request->userid))
            {
                
                $userid = Auth::guard('web')->user()->id;

                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/applications/'.$documents->application));
                    //UserDocument::where('user_id', $userid)->delete();
                }

            } else {
                $userid = $request->userid;
                
                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/applications/'.$documents->application));
                    //UserDocument::where('user_id', $request->user()->id)->delete();
                }
            }
        } 
        
        if ($request->has('custppphoto')) {
            if (empty($request->userid))
            {
                
                $userid = Auth::guard('web')->user()->id;

                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/clientphoto/'.$documents->customppphoto));
                    //UserDocument::where('user_id', $userid)->delete();
                }

            } else {
                $userid = $request->userid;
                
                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/clientphoto/'.$documents->customppphoto));
                    //UserDocument::where('user_id', $request->user()->id)->delete();
                }
            }
        } 
        
        if ($request->has('tradelicence')) {
            if (empty($request->userid))
            {
                
                $userid = Auth::guard('web')->user()->id;

                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/tradelicence/'.$documents->tradelicence));
                    //UserDocument::where('user_id', $userid)->delete();
                }

            } else {
                $userid = $request->userid;
                
                if (ResellerDocument::where('reseller_id', $userid)->exists())
                {
                    $documents = ResellerDocument::where('reseller_id', $userid)->first();
                    @unlink(public_path('reseller/tradelicence/'.$documents->tradelicence));
                    //UserDocument::where('user_id', $request->user()->id)->delete();
                }
            }
        }


        if ($request->has('nationalid') || (
            $request->has('application') ||
            $request->has('custppphoto') ||
            $request->has('tradelicence'))
        ) {

            if (Auth::guard('web')->check())
            {
                $usertype = 'web';

            } elseif (Auth::guard('root')->check())
            {
                $usertype = 'root';

            } elseif(Auth::guard('manager')->check())
            {
                $usertype = 'manager';

            } elseif(Auth::guard('reseller')->check())
            {
                $usertype = 'reseller';

            } else {

                $usertype = null;
            }

            if (!ResellerDocument::where('reseller_id', $userid)->exists()) {
                $documents = ResellerDocument::create([
                    'reseller_id' => !empty($request->userid) ? $request->userid : Auth::guard('reseller')->user()->id,
                    'nid' => $this->nationalid,
                    'application' => $this->application,
                    'customppphoto' => $this->custppphoto,
                    'tradelicence' => $this->tradelicence,
                    'root_user_id' => !empty(Auth::guard('root')->user()->id) ? Auth::guard('root')->user()->id : 1,
                    'manager_id' => !empty(Auth::guard('manager')->user()->id) ? Auth::guard('manager')->user()->id : 0, 
                    'parent_reseller_id' => $request->parent_reseller_id,
                    'user_type' => $usertype,
                ]);
            } else {
                $userid = !empty($request->userid) ? $request->userid : Auth::guard('web')->user()->id;

                if ($request->has('nationalid')) {
                    $documents = ResellerDocument::where('reseller_id', $userid)->update([
                        'nid' => $this->nationalid
                    ]);
                } 
                
                if ($request->has('application')) {
                    $documents = ResellerDocument::where('reseller_id', $userid)->update([
                        'application' => $this->application
                    ]);
                } 
                
                if ($request->has('custppphoto')) {
                    $documents = ResellerDocument::where('reseller_id', $userid)->update([
                        'customppphoto' => $this->custppphoto
                    ]);
                } 
                
                if ($request->has('tradelicence')) {
                    $documents = ResellerDocument::where('reseller_id', $userid)->update([
                        'tradelicence' => $this->tradelicence
                    ]);
                }
            }
        }
    }


    public function showUserDocuments(Request $request)
    {
        $userid = !empty($request->userid) ? $request->userid : Auth::guard('reseller')->user()->id;
        return ResellerDocument::where('reseller_id', $userid)->first();
    }

    public function verifyDocument(Request $request)
    {
        if (Auth::guard('root')->check() ||
            Auth::guard('manager')->check() ||
            Auth::guard('reseller')->check()
        )
        {
            
           
            $userid = !empty($request->userid) ? $request->userid : Auth::guard('reseller')->user()->id;

            $user = Reseller::where('id',$userid)->first();

            if (! empty($user)) {
                $documents = ResellerDocument::where('reseller_id', $user->id)->first();

                if ($documents) {
                    if($documents->isVerified == 0) {
                        $verify = 1;

                        //if user registered from client login page, and need to phoe verified
                        //from root verification phone verified don't need
                        $user->verified = "y";
                        $user->security_code = null;
                        $user->phone_verified = true;
                        $user->save();
                    }
        
                    if($documents->isVerified == 1) {
                        $verify = 0;
                    }

                    $documents->isVerified = $verify;
                    $documents->save();

                    return response()->json(['msg' => $documents->isVerified], 200);
                } else {
                    return response()->json(['errmsg' => 'Reseller document missing'], 406);
                }
            }

            return response()->json(['errmsg' => 'Reseller not found'], 406);
            
        }

        return response()->json(['errmsg' => 'Reseller not found'], 406);
    }


    public function changeClientStatus(Request $request)
    {
        if ((Auth::guard('root')->check() ||
            Auth::guard('manager')->check() ||
            Auth::guard('reseller')->check()) &&
            isset($request->userid)
        )
        {
            

            $userid = !empty($request->userid) ? $request->userid : Auth::guard('reseller')->user()->id;
            $user = Reseller::where('id',$userid)->first();

            if (! empty($user)) {
                
                if (@Auth::guard('root')->user()->id || $user->manager_id == @Auth::guard('manager')->user()->id)
                {
                    if ($user->status == 'y')
                    {
                        $changeuser = Reseller::where('id', $user->id)->first();
                        $changeuser->status = 'n';
                        $changeuser->save();
                    } else {

                        $changeuser = Reseller::where('id', $user->id)->first();
                        $changeuser->status = 'y';
                        $changeuser->save();
                    }
                }

                return response()->json(['msg' => $changeuser->status], 200);
            }

            return response()->json(['errmsg' => 'Reseller not found'], 406);
            
        }

        return response()->json(['errmsg' => 'Reseller not found'], 406);
    }
}