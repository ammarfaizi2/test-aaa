<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Checklist;
use Illuminate\Http\Request;


if (!function_exists("handleInternalError")) {
	/**
	 * @param \Closure $callback
	 * @param bool &$error
	 */
	function handleInternalError(Closure $callback, bool &$error)
	{
		try {
			return $callback();
		} catch (Error $e) {
			$error = $e;
			return false;
		}
	}
}

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get("/checklists/{checklistId}", function ($checklistId) {
	dd($checklistId);
});

$router->post('/checklists', function (Request $request) {

	$this->validate($request, [
        'data' => 'required',
        'data.attributes' => 'required',
        'data.attributes.object_domain' => 'required',
        'data.attributes.object_id' => 'required',
        'data.attributes.description' => 'required',
        'data.attributes.due' => 'date'
    ]);

	$data = $request->json()->all();

	$checklist = new Checklist();
	foreach (['object_domain', 'object_id', 'due', 'urgency', 'description', 'task_id'] as $k) {
		$checklist->{$k} = $data["data"]["attributes"][$k];
	}

	$checklist->created_by = Auth::user()->id;

	$error = false;

	return response()->json(
		handleInternalError(function () use ($checklist) { 
			$checklist->save();
			$ret = [
				"data" => [
					"type" => "checklists",
					"id" => $checklist->id,
					"attributes" => $checklist->toArray(),
					"links" => sprintf("%s/api/v1/checklists/%d", 
						env("APP_URL"), $checklist->id)
				]
			];

			// dd($ret);

			$ptr = &$ret["data"]["attributes"];
			$ptr["completed_at"] = $checklist->getCompletedAt();
			$ptr["is_completed"] = $checklist->isCompleted();
			$ptr["updated_by"] = $checklist->getUpdatedBy();
			$ret["data"]["id"] = $ret["data"]["attributes"]["id"];

			unset($ret["data"]["attributes"]["id"], $ptr);

			return $ret;
		}, $error), $error !== false ? 500 : 200);
});

