<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Models\Venue;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TeamController extends Controller
{
    public function ShowTeamList(Request $request)
    {
        if ($request->ajax()) {
            $teams = Team::with('homeVenue')->get();
            return DataTables::of($teams)
                ->addColumn('home_venue_name', function ($team) {
                    return $team->homeVenue->name;
                })
                ->addColumn('actions', function ($row) {
                    return "<a href='" . route('get.team-players', $row->id) . "' class='btn btn-sm btn-primary px-2 mr-2'><i style='font-size: 12px' class='me-1 fas fa-users'></i> Players</a>
                            <a href='" . route('get.team-update', $row->id) . "' class='btn btn-sm btn-success px-2 mr-2'><i style='font-size: 12px' class='me-1 fas fa-wrench'></i> Update</a>
                            <form action='" . route('team.delete', $row->id) . "' method='POST' class='d-inline-block'>
                                " . csrf_field() . "
                                " . method_field('DELETE') . "
                                <button type='submit' class='btn btn-sm btn-danger px-2' onclick='return confirm(\"Are you sure you want to delete this team?\")'> <i style='font-size: 12px' class='me-1 fas fa-trash'></i> Delete</button>
                            </form>";
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        $teams = Team::count();
        return view('pages.teams.teamList')->with('teams', $teams);
    }
    public function ShowAddTeamForm()
    {
        $venues = Venue::all();
        return view('pages.teams.addTeam', ['venues' => $venues]);
    }
    public function AddTeam(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'head_coach' => 'required',
            'home_venue_id' => 'required|numeric'
        ]);
        $venue = new Team();
        $venue->name = $request->name;
        $venue->head_coach = $request->head_coach;
        $venue->home_venue_id = $request->home_venue_id;
        $venue->save();
        return redirect('teams')->withSuccess('team added successfully!');
    }
    public function UpdateTeamForm($id)
    {
        $team = Team::find($id);
        $homeVenue = $team->home_venue_id;
        $venues = Venue::all();
        if (!$team) {
            return redirect('teams')->withDanger('No team found for update!');
        }
        return view('pages.teams.updateTeam', ['team' => $team, 'venues' => $venues, 'homeVenue' => $homeVenue]);
    }
    public function UpdateTeam(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'head_coach' => 'required',
            'home_venue_id' => 'required|numeric'
        ]);
        $check = Team::find($request->id);
        if (!$check) {
            return redirect()->back()->withError('No team found for update!');
        } else {
            $team =  Team::find($request->id);
            $team->name = $request->name;
            $team->head_coach = $request->head_coach;
            $team->home_venue_id = $request->home_venue_id;
            $team->save();
            return redirect('teams')->withSuccess('teams update successfully!');
        }
    }
    public function DeleteTeam($id)
    {
        $team = Team::find($id);
        if ($team) {
            $team->delete();
            return redirect()->route('teams')->with('success', 'Team id ' . $id . ' deleted successfully!');
        } else {
            return redirect()->route('teams')->with('success', 'Team record not found!');
        }
    }
    public function getTeamAllPlayersForm($id, Request $request)
    {
        $team = Team::find($id);
        if (!$team) {
            return redirect('teams')->withDanger('team id ' . $id . ' not found');
        } else {
            $players = $team->teamPlayers;
            // combine all payer team id with team name
            $players->map(function ($player) {
                $player->team_name = $player->team->name;
                return $player;
            });

            if ($request->ajax()) {
                return DataTables::of($players)->make(true);
            }
            return view(
                'pages.teams.teamPlayers.teamPlayerList',
                ['team' => $team, 'players' => $players]
            );
        }
    }
}