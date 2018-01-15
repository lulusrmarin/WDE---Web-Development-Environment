<?php
    function get_directory_info($dir) {
	    $scan = scandir($dir);
	    
	    $list = [];
    	foreach( $scan as $item ) {
    	    $fullPath = "$dir/$item";
    	    if( $item != ".." && $item != "." ) { //Ignore some directories for now
        	    $list[ $item ]['directory'] = is_dir( $fullPath );
        	    $list[ $item ]['permissions'] = substr(sprintf('%o', fileperms( $fullPath ) ), -4);
        	    $list[ $item ]['path'] = $fullPath;
        	    $list[ $item ]['fullLookup'] = get_directory_tree( $list[ $item ]['path'] );
    	    }
    	}    
    	
    	return $list;
    }

    function get_directory_tree($dir) {
        $segments = explode( "/",  $dir );
        foreach( $segments as $segment) {
            if( $segment ) {
                $newPath .= "/$segment";
                $lookup[$segment] = $newPath;
            }
        }
        return $lookup; 
    }

    if( $_GET ) {
        $data = [];

        if( $_GET['files'])
    	    $data['fileList'] = get_directory_info( ( $_GET['dir'] ? $_GET['dir'] : __DIR__ ) );
        if( $_GET['lookup']) {
            $data['lookup'] = get_directory_tree(__DIR__);
        }
        
        echo json_encode( $data );
        die();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/common.css">    
    
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.4/angular.min.js"></script>
</head>
<body>
	<div ng-app="folders" ng-controller='ctrl'>
	    <tree dir="root"></tree>
	</div>
</body>
</html>

<script>
    var folders = angular.module( 'folders', [] );
    folders.controller( 'ctrl', function( $scope, $http ){ 
        $scope.node = {};
        
        $scope.getDirectoryInfo = function( dir, scopeVar ) {
            $http.get('index.php?files=true&dir=' + dir).then( 
                function( response ){
                    console.log( "Assigning to " + scopeVar)
                    console.log( response.data );
                    $scope[ scopeVar ] = response.data.fileList;
            });            
        }
        
        $scope.getDirectoryInfo( '/', 'root' );
    });
    
    folders.directive('tree', function() {
    	return{
    		restrict: 'E',
    		template: '<div ng-repeat="( filename, info ) in dir track by $index">'
                + ' <div class="w3-border">'
                + '     <i class="fa fa-plus-square pointer w3-text-green" aria-hidden="true" ng-click="getDirectoryInfo( filename, node[ info.path ] )" ng-if="info.directory"></i>'
                + '     {{filename}}'
	            + ' </div>'
                //+ ' <tree dir="info.path">'	            
	            + '</div>',
    		scope: {
    			dir: '='
    		}
    	};
    });     
</script>
