<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Laravue\Models\User;
use App\Models\Warehouse\Site;
use Illuminate\Http\Request;

class SitesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        $warehouse_id = $request->warehouse_id;
        if ($user->isAdmin()) {
            $sites = Site::with('users')->where('warehouse_id', $warehouse_id)->get();
        } else {

            $sites = $user->sites;
        }

        return response()->json(compact('sites'));
    }

    public function assignableUsers()
    {
        $staff_users = User::where('user_type', 'staff')->get();
        $assignable_users = [];
        foreach ($staff_users as $user) {
            if (!$user->isAdmin() && !$user->isAssistantAdmin()) {
                $assignable_users[] = $user;
            }
        }

        return response()->json(compact('assignable_users'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addUserToSite(Request $request)
    {
        //
        $user_ids = $request->user_ids;
        $site_id = $request->site_id;
        $site = Site::find($site_id);
        $site->users()->syncWithoutDetaching($user_ids);
        $site_users = $site->users;
        // $site_user = UserSite::where(['user_id'=> $user_id, 'site_id' => $site_id])->first();

        // if (!$site_user) {
        //     $site_user = new UserSite();
        //     $site_user->user_id = $user_id;
        //     $site_user->site_id = $site_id;
        //     $site_user->save();
        // }
        $actor = $this->getUser();
        $title = "Staff assigned to $site->name";
        $description = "Staff assigned to $site->name by $actor->name ($actor->email)";
        //log this activity
        $roles = ['assistant admin', 'site manager'];
        $this->logUserActivity($title, $description, $roles);
        return response()->json(compact('site_users'), 200);
    }
    public function removeUserFromSite(Request $request)
    {
        //
        $user_ids = $request->user_ids;
        $site_id = $request->site_id;
        $site = Site::find($site_id);
        $site->users()->detach($user_ids);
        $site_users = $site->users;
        // $site_user = UserSite::where(['user_id'=> $user_id, 'site_id' => $site_id])->first();

        // if (!$site_user) {
        //     $site_user = new UserSite();
        //     $site_user->user_id = $user_id;
        //     $site_user->site_id = $site_id;
        //     $site_user->save();
        // }
        $actor = $this->getUser();
        $title = "Staff assigned to $site->name";
        $description = "Staff assigned to $site->name by $actor->name ($actor->email)";
        //log this activity
        $roles = ['assistant admin', 'site manager'];
        $this->logUserActivity($title, $description, $roles);
        return response()->json(compact('site_users'), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Site\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function show(Site $site)
    {
        //
        return response()->json(compact('site'), 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request, Site $site)
    {
        //
        $name = $request->name;
        $address = $request->address;
        $warehouse_id = $request->warehouse_id;
        $site = Site::where('name', $name)->first();

        if (!$site) {
            $site = new Site();
            $site->warehouse_id = $warehouse_id;
            $site->name = $name;
            $site->address = $address;
            $site->save();
        }
        $actor = $this->getUser();
        $title = "New site created";
        $description = "$actor->name ($actor->email) created $site->name";
        //log this activity
        $roles = ['assistant admin', 'site manager'];
        $this->logUserActivity($title, $description, $roles);
        return $this->show($site);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Site\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Site $site)
    {
        //
        $site->name = $request->name;
        $site->warehouse_id = $request->warehouse_id;
        $site->address = $request->address;
        $site->enabled = $request->enabled;
        $site->save();

        $actor = $this->getUser();
        $title = "Updated site information";
        $description = "$actor->name ($actor->email) updated $site->name information";
        //log this activity
        $roles = ['assistant admin', 'site manager'];
        $this->logUserActivity($title, $description, $roles);
        return $this->show($site);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Site\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function destroy(Site $site)
    {
        //
        $actor = $this->getUser();
        $title = "Deleted $site->name";
        $description = "$actor->name ($actor->email) deleted $site->name information";
        //log this activity
        $roles = ['assistant admin', 'site manager'];
        $this->logUserActivity($title, $description, $roles);
        $site->delete();
        return response()->json(null, 204);
    }
}
