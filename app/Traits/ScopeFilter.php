<?php

namespace App\Traits;

use Illuminate\Http\Request;

use App\Models\Branch;
use App\Models\Business;

trait ScopeFilter {
    protected function scopeFilterList($query, Request $request, $businessShared = false, $options = []) {
        $branchKey = $options['branch_key'] ?? 'branch_id';
        $businessKey = $options['business_key'] ?? 'business_id';
        $relation = $options['relation'] ?? 'branch';

		$scope = $request->user_scope;

		if($scope == 'global') {
			return $query;
		}

		if(!$businessShared) {
			if($scope == 'business') {
				return $query->whereHas($relation, function ($q) use ($request, $businessKey) {
					$q->where($businessKey, $request->user_business_id);
				});
			}

			return $query->where($branchKey, $request->user_branch_id);
		}

        return $query->where(function($q) use ($request, $relation, $businessKey, $branchKey, $scope) {
            $q->where('scope', 'global');
            
            $q->orWhere(function($q2) use ($request, $relation, $businessKey) {
                $q2->where('scope', 'business')
                   ->whereHas($relation, function($q3) use ($request, $businessKey) {
                       $q3->where($businessKey, $request->user_business_id)
                          ->where('is_main', true); 
                   });
            });
            
			$q->orWhere(function($q4) use ($request, $relation, $businessKey, $branchKey, $scope) {
				if($scope == 'business') {
					$q4->whereHas($relation, function($q5) use ($request, $businessKey) {
						$q5->where($businessKey, $request->user_business_id);
					});
				} else {
					$q4->where($branchKey, $request->user_branch_id);
				}
			});
        });
	}

	protected function assignScope(array $data, Request $request) {
		$key = 'branch_id';

		if (!isset($data[$key])) {
			$data[$key] = $request->user_branch_id;
		} 
		
		elseif($data[$key] == 'all_business') {
			$data[$key] = $request->user_branch_id;
			$data['scope'] = 'global';
		} 
		
		elseif (str_starts_with($data[$key], 'all_branches-')) {
			$branchId = substr($data[$key], strlen('all_branches-'));
			$data['scope'] = 'business';
			$data[$key] = intval($branchId);
		} 

		if(!isset($data['scope'])) {
			$data['scope'] = 'branch';
		}

        return $data;
	}

	protected function validateShow(array $data, Request $request, $options = []): bool {
		$branchKey = $options['branch_key'] ?? 'branch_id';
		$scope = $request->user_scope;

		$dataId = $data['id'];

		if($scope == 'branch'){
			return $data[$branchKey] == $request->user_branch_id;
		} elseif ($scope == 'business') {

			$branchIds = Branch::where('business_id', $request->user_business_id)->pluck('id')->toArray();

			if (empty($branchIds)) return false;
		
			return in_array($data[$branchKey], $branchIds);
		}

		return true;
	}
	
	protected function assignBusiness($element, $branchKey = 'branch_id', $businessKey = 'business_id') {
		$branchId = is_array($element) 
			? ($element[$branchKey] ?? null)
			: ($element->{$branchKey} ?? null);

		if (!$branchId) {
			return $element;
		}

		$branch = Branch::find($branchId);
		if (!$branch) {
			throw new \RuntimeException("Branch with id $branchId not found");
		}

		$business = Business::find($branch->business_id);
		if (!$business) {
			throw new \RuntimeException("Business with id {$branch->business_id} not found");
		}

		if (is_array($element)) {
			$element[$businessKey] = $branch->business_id;
			$element['business'] = $business->name;
			$element['branch'] = $branch->name;
		} else {
			$element->{$businessKey} = $branch->business_id;
			$element->business = $business->name;
			$element->branch = $branch->name;
		}

		return $element;
	}

	protected function invalidScopeResponse(){
		return response()->json([
			'message' => 'user scope invalid',
			'success' => false,
		], 403);
	}
}