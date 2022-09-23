<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Property;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function create(Request $request)
    {
        if (!Property::find($request->request->get("property_id"))) {
            Investment::firstOrCreate(["property_id" => $request->request->get("property_id"), "user_id" => $request->request->get("user_id")], $request->all());
            return response()->json([
                "status" => true,
                "message" => "Investment created successfully."
            ], 201);
        }else{
            return response()->json([
                "status" => false,
                "message" => "Property not found."
            ], 404);
        }
    }

    public function read(Request $request)
    {
        if (sizeof(Investment::where("user_id", $request->request->get("user_id"))->get()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "Investment data retrieved successfully.",
                "data" => Investment::where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }

    public function readAll()
    {
        if (sizeof(Investment::all()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "All investment data retrieved successfully.",
                "data" => Investment::all()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "No investment data found."
            ], 404);
        }
    }

    public function readSpecific(Request $request)
    {
        if (sizeof(Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()) > 0) {
            return response()->json([
                "status" => true,
                "message" => "Investment data retrieved successfully.",
                "data" => Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }

    public function update(Request $request)
    {
        if (sizeof(Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->get()) > 0) {
            Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->update($request->all());
            return response()->json([
                "status" => true,
                "message" => "Investment data updated successfully.",
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Investment data not found."
            ], 404);
        }
    }

    public function delete(Request $request)
    {
        Investment::where("property_id", $request->request->get("property_id"))->where("user_id", $request->request->get("user_id"))->delete();
        return response()->json([
            "status" => true,
            "message" => "Investment data deleted successfully.",
        ], 200);
    }
}
