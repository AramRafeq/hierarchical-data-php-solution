<?php
/*
Auther: Aram Rafeq
Site: developerstree.com
Contact Me: aramrafeq2@gmail.com
*/
function getAdjacencyList($con)
{
	$data = [];
	$index = [];

	/* Below query will be changed according to your needs by the 7
	query you get an idea how to use the code for your own use */	
	$query  =$con->query("SELECT accounts.AccountId,Code,
								accounts.Name AS title,
								accounts.Name AS Name,
								currencies.Name AS Currency,
								accounts.CurrencyId AS CurrencyId,
								accounts.AcceptPayments,
								accounts.ParentAccountId,
								Notes AS tooltip,
								Description,
								accounts.Notes AS Notes,
								(SELECT true) AS expanded,
								SystemAccount,
								accountgroups.Name AS AccountGroup,
								accounttypes.Name AS AccountType,
								accounttypes.AccountTypeId AS AccountTypeId,
								Balance
								FROM 
								accounts,accountgroups,currencies,accounttypes,accountbalances WHERE 
								accounts.Deleted='0'    AND 
								accountbalances.AccountId=accounts.AccountId AND 
								currencies.CurrencyId=accounts.CurrencyId AND
								accounts.AccountTypeId = accounttypes.AccountTypeId AND
								accountgroups.AccountGroupId = accounttypes.AccountGroupId
			") or die("error in chhildren Query");

		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		
		for ($i=0; $i <count($rows) ; $i++) { 
			$row = $rows[$i];
			$id = $row["AccountId"]; // customize this line
		    $parent_id = $row["ParentAccountId"] === NULL ? "NULL" : $row["ParentAccountId"]; // customize this line
		    $data[$id] = $row;
		    $index[$parent_id][$id] = $id;
		}

	return ["data"=>$data,"indexes"=>$index];
	
}
function get_child_nodes($db,$parent_id)
{
    $children = array();

    $list = getAdjacencyList($db);
	
	$index = $list['indexes'];
	$data = $list['data'];
    $parent_id = $parent_id === NULL ? "NULL" : $parent_id;
    if (isset($index[$parent_id])) {
        foreach ($index[$parent_id] as $id) {
            $children[] = $id;
            $children = array_merge($children, get_child_nodes($db,$id));
        }
    }
    return $children;
}
function initLevels($indexes,$top)
{
	$count = 3;
	$path = "";
	while ($count>0) {
		foreach ($top as $key => $element) { 
			if(is_array($element)){
					$top[$key] = initLevels($indexes,$top[$key]);
			}else{
				if(array_key_exists($element,$indexes)){
					unset($top[$key]);
					$top[$element] = $indexes[$element];
				}
			}
		}
		$count--;
	}
	
	return $top;
}
function getTree($data,$levels)
{

	foreach ($levels as $key => $level) {
		if(is_array($level)){
			$tmp =  getTree($data,$level);			
			$levels[$key] = $data[$key];
			$levels[$key]['children'] = $tmp;
		}else{
			$levels[$key] = $data[$key];

		}
	}
	return $levels;
}
function prepare($tree)
{
	$tree = array_values($tree);
	foreach ($tree as $key => $node) {
		if(is_array($node) && isset($node['children'])){
			$node['children'] = array_values($node['children']);
			$node['children'] = prepare($node['children']);
			$tree[$key] = $node;
		}
	}
	return $tree;
}


/*Usage Example*/
$data = getAdjacencyList($db);
if(count($data['indexes'])>0){
	$levels = initLevels($data['indexes'],$data['indexes']['NULL']);
	$data = getTree($data['data'],$levels);	// returned data as arrays which have childrens
	
}


?>