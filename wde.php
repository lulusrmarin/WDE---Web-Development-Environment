<?php
    // WebDE  v0.92
    /*
        Bug01 - 
        Status bar at the bottom should update saved via CTRL-S binding.  It does not do this
        
        Bug02
        New files seem to not save in the directory chosen
        
    // Todo:  Add in Github API
    // Todo:  Create development build
    // Todo:  Create use build    
    // Todo:  Add ability to install certain scripts
    // Todo:  Add ability to webSSH
    // Todo:  Separate controllers further
    // Todo:  Add drag/drop folers
    // Todo:  Add ability to cut copy and paste files
    // Todo:  Add in hotkeys
    // Todo:  Merge Tippy And Angular more effectively
    // Todo:  More satisfactory integration of status bar with hotkey bindings
    // Todo:  Add MYSQL window
    */

    // Ugly Angular Fix for seralized POST values
    $_POST = json_decode(file_get_contents('php://input'),true);

    function get_files_and_directories($dir) {
	    $scan = scandir($dir);        
	    
	    $list = [];
    	foreach( $scan as $item ) {
    	    $fullPath = "$dir" . ( $dir == "/" ? null : "/" ) . "$item";
    	    
    	    if( is_dir( $fullPath ) ) 
    	        $type = 'directories';
            else
                $type = 'files';
    	    if( $item != ".." && $item != "." ) { //Ignore some directories for now    
        	    $list[ $type ][ $item ]['directory'] = is_dir( $fullPath );
        	    $list[ $type ][ $item ]['permissions'] = substr(sprintf('%o', fileperms( $fullPath ) ), -4);
        	    $list[ $type ][ $item ]['path'] = $fullPath;
        	    $list[ $type ][ $item ]['fullLookup'] = get_directory_tree( $list[ $item ]['path'] );
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

    function check_path( $path ) {
        if( is_dir( $_POST['saveDir'] ) || file_exists( $_POST['saveDir'] ) )
            return false;
        else
            return true;
    }

    if( $_GET || $_POST ) {
        $data = [];

        if( $_GET['files'])
    	    $data['fileList'] = get_files_and_directories( ( $_GET['dir'] ? $_GET['dir'] : __DIR__ ) );
        if( $_GET['pwd'])
    	    $data['workingDir'] = getcwd();    	    
        if( $_GET['loadFile'])
    	    $data['fileString'] = file_get_contents( $_GET['path'] );
    	    
        if( $_POST['savePath']) 
            $data['saved'] = file_put_contents( $_POST['savePath'], $_POST['data'] );
        if( $_POST['saveDir']) {
            if( check_path( $_POST['saveDir'] ) ) {
                $old_umask = umask(0);
                if( mkdir( $_POST['saveDir'], octdec( $_POST['mode'] ) ) ) {
                    $data['status'] = "Directory Created";
                }
            }
            else
                $data['status'] = "There was a problem creating this directory";
        }
        
        
        if( $_POST['deletePath']) {
            if( unlink( $_POST['deletePath'] ) )
                $data['status'] = "{$_POST['deletePath']} file deleted.";
            else
                $data['status'] =  "There was a problem deleting {$_POST['deletePath']}";
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
    <link rel="stylesheet" href="css/style.css">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">    
    
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.4/angular.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js"></script>
    <script src="https://unpkg.com/tippy.js@2.1.1/dist/tippy.all.min.js"></script>
    
    <title>MMWIDE v1.0</title>
</head>
<body>
    <div ng-app="mide" ng-controller='ctrl'>
        <!-- Menu -->
        <div class="dark w3-col w3-display-container s1" id="menu">
            <div class="w3-display-left">
                <ma-button color="blue" tag="folder-open" ng-click="toggle('newFolder')" class="tt" title="Create a new folder">New Folder</ma-button>
                <ma-button color="green" tag="floppy-o" ng-click="save( currentTab )"  class="tt" title="Save a file">Save</ma-button>
                
                <!-- Todo: Future Functionality -->
                <!-- ma-button color="dark-grey" tag="github">Github</ma-button>
                <ma-button color="red" tag="tasks">Install</ma-button>
                <ma-button color="indigo" tag="database">Database</ma-button>
                <ma-button color="light-grey" tag="check" ng-click="testButt()">Test Fire</ma-button -->
                <!-- Future Functionality -->
                
                <select class="w3-round" ng-model="theme" ng-options="option as option for option in themes" ng-change="select_theme()">
                    <option value="">-- Select Theme</option>
                </select>
            </div>
        </div>
        <!-- Menu -->
        
        <!-- Hidden popup fields -->
        
        <!-- New Folder -->
        <div class="s2 w3-light-grey w3-round" id="newFolder" ng-if="tog.newFolder">
            <!-- Todo:  Bug here were input size doesn't always match string size -->
            <input type="text"ng-model ="$root.currentDir" size={{inputSize}}>
            <!-- Todo:  Bug here were input size doesn't always match string size -->
            <ma-button color="green" tag="users" ng-click="toggle( 'permissions' )" class="tt" title="Change Permissions (Default: 777)">Permissions</ma-button>
            <ma-button color="blue" tag="folder-open" ng-click="saveDir()">Save</ma-button>
            <ma-button color="red" tag="times-circle" ng-click="off('newFolder')" class="tt" title="Hide"></ma-button>
            <br>
            
            <!-- Permissions -->
            <!-- Todo:  Create Directives for these -->
            <div class="w3-round w3-light-grey" id="chmod" ng-if="tog.permissions" align="right">
                <table width="50%" class="w3-round w3-dark-grey w3-margin-top">
                    <th></th>
                    <th>Owner</th>
                    <th>Group</th>
                    <th>Others</th>
                    <tr align="center">
                        <td>Execute</td>
                        <td><input type="checkbox" ng-change="chModeBits( 0, 1 )" ng-model="tog.modeBits.owner.execute"></td>
                        <td><input type="checkbox" ng-change="chModeBits( 1, 1 )" ng-model="tog.modeBits.group.execute"></td>
                        <td><input type="checkbox" ng-change="chModeBits( 2, 1 )" ng-model="tog.modeBits.other.execute"></td>
                    </tr>
                    <tr align="center">
                        <td>Write</td>
                        <td><input type="checkbox" ng-change="chModeBits( 0, 2 )" ng-model="tog.modeBits.owner.write"></td>
                        <td><input type="checkbox" ng-change="chModeBits( 1, 2 )" ng-model="tog.modeBits.group.write"></td>
                        <td><input type="checkbox" ng-change="chModeBits( 2, 2 )" ng-model="tog.modeBits.other.write"></td>              
                    </tr>
                    <tr align="center">
                        <td>Read</td>
                        <td><input type="checkbox" ng-change="chModeBits( 0, 4 )" ng-model="tog.modeBits.owner.read"></td>
                        <td><input type="checkbox" ng-change="chModeBits( 1, 4 )" ng-model="tog.modeBits.group.read"></td>
                        <td><input type="checkbox" ng-change="chModeBits( 2, 4 )" ng-model="tog.modeBits.other.read"></td>                    
                    </tr>
                    <tr align="center">
                        <td></td>
                        <td>{{modeBits[0]}}</td>
                        <td>{{modeBits[1]}}</td>
                        <td>{{modeBits[2]}}</td>
                    </tr>                
                </table>
            </div>
            <!-- Permissions -->            
        </div>
        <!-- New Folder -->
        
        <!-- New File -->
        <div class="s2 w3-light-grey w3-round" id="newFile" ng-if="tog.newFile">
            <span class="w3-small">Creating New File in: <b>{{currentDir}}</b></span><br>
            <input type="text" placeholder="Filename" ng-model="filename">
            <ma-button color="blue" tag="floppy-o" ng-click="saveFile( currentDir + '/' + filename )">Save</ma-button>
            <ma-button color="red" tag="times-circle" ng-click="off('newFile')"></ma-button><br>
            <br>
        </div>        
        <!-- New File -->
        
    	<!-- Hidden popup fields -->
    	
    	<!-- Tree Menu -->
    	<div class="dark" id="tree-menu" ng-controller='folders'>    
    	    <tree dir="/" depth="0" ng-init="$root.loadTree()"></tree>
    	</div>
        <!-- Tree Menu -->
        
        <div class="dark w3-bar w3-small" id="tabs" ng-controller="folders">
            <button class="w3-bar-item w3-border w3-round-large w3-button w3-text-yellow animated" ng-click="loadTab('new')">
                <span class="animated flash">
                <i class="fa fa-file w3-text-light-grey" aria-hidden="true"></i> New
                </span>
            </button>
            
            <tab color="white" ng-repeat="( path, filename ) in $root.tabs" path="{{path}}" autoscroll="true">{{filename}}</tab>
            
            <!-- Editor -->
            <div id="editor" ng-init="loadTab('new')"></div> 
            <!-- Editor -->
        </div>   
        
        <div class="dark" id="status"><span class="animated" id="statusBar">{{statusBar}}</span></div>
    </div>
</body>
</html>

<script>
    // Tippy
    tippy('.tt');

    // Standard Marin Library Bullshit
    function cl(s) { console.log(s); }

    // Ace
    var editor = ace.edit("editor");
    editor.session.setMode("ace/mode/javascript");

    // Angular
    var app = angular.module( 'mide', [] );
    app.controller( 'folders', function( $scope, $http, $location, $anchorScroll, $timeout ){ 
        $scope.node = {};
        if(!$scope.$root.tabs)
            $scope.$root.tabs = {
                
            };
        if(!$scope.$root.locals)
            $scope.$root.locals = { 'new': 'function foo(items) {\n'
                + '\tvar x = "All this is syntax highlighted";\n'
                + '\treturn x;\n'
                + '}'
            };            
        
        $scope.getDirectoryInfo = function( dir, scopeVar ) {
            $http.get('index.php?files=true&dir=' + dir).then( 
                function( response ){
                    $scope.$root.currentDir = dir;
                    $scope.node[ scopeVar ] = response.data.fileList;
                    cl($scope.$root.currentDir);
            });       
        }
        
        $scope.loadFile = function( file ) {
            $http.get('index.php?loadFile=true&path=' + file).then( 
                function( response ){
                    if( response.data.fileString ) {
                        extension = file.split("/").slice(-1)[0].split(".").slice(-1)[0];
                        cl(extension);
                        editor.setValue( response.data.fileString, 1 );
                        editor.session.setMode("ace/mode/" + extension );
                        $scope.addTab( file  );
                        $scope.loadLocalCopy( file, response.data.fileString );
                        $scope.$root.currentTab = file;
                        cl( $scope.$root.currentTab );
                    }
                    else  
                        editor.setValue( "Problem Loading File" );
            });           
        }
        
        $scope.addTab = function( path ) {
            segments = path.split("/")
            filename = segments[ segments.length - 1 ]
            if( !$scope.$root.tabs[ path ] )
                $scope.$root.tabs[ path ] = filename;
            cl($scope.$root);  
        }
        
        $scope.loadTab = function( path ) {
            $scope.$root.locals[ $scope.$root.currentTab ] = editor.getValue();
            editor.setValue( $scope.$root.locals[ path ], 1 );
            $scope.$root.currentTab = path;
        }
        
        $scope.closeTab = function( path ) {
            $scope.loadTab('new');
            delete( $scope.$root.locals[ path ] );
            delete( $scope.$root.tabs[ path ] );
        }
        
        $scope.loadLocalCopy = function( path, fileString ) {
            if( !$scope.$root.locals[ path ] )
                $scope.$root.locals[ path ] = fileString;
            cl($scope.$root.locals);              
        }
        
        $scope.expandTree = function( path ){ 
            var location = path.split('/');
            var s = "";
            location.forEach( function( dir ){ 
                if( dir )
                    s += "/";
                s += dir;
                $scope.getDirectoryInfo( ( dir ? s : "/" ), ( dir ? s : "/" ) );
            });               
        }
        
        $scope.Pwd = function(){
            $http.get('index.php?pwd=1').then(
                function( response ){
                    path = response.data.workingDir
                    $scope.expandTree( path );
                });      
        }
        
        $scope.$root.loadTree = function( location ) {
            if( location )
                $scope.expandTree( location );
            else 
                $scope.Pwd();
            
            $timeout( function(){ $scope.scrollToTarget('level' + $scope.$root.deep) }, 1000 );  
        }
        
        $scope.collapse = function( dir ) {
            delete $scope.node[ dir ];
        }
        
        // Todo: wait for page to load before anchor scrolling instead of using hacky random timeout
        $scope.scrollToTarget = function(s) {
            $location.hash(s);
            $anchorScroll();             
        }
        
        $scope.getLastPath = function( path ) {
            var segments = path.split("/")
            segments.pop()
            return segments.join("/");
        }
        
        $scope.deleteFile = function( path ) {
            if( confirm("Are you sure you want to delete file: " + path + "?") ) {
                $http.post('index.php', { deletePath: path }).then( 
                    function( response ){
                        alert(response.data.status);
                        $scope.$root.loadTree( $scope.getLastPath( path ) );
                });    
            }
        }
    });
    
    app.controller( 'ctrl', function( $scope, $http ){  
        $scope.tog = {};
        $scope.test = function(){ cl( 'test fire' )};
        $scope.inputSize = 30;
        
        // Todo:  There is something wrong with this, but I need to figure it out.
        $scope.modeBits = [ 7, 7, 7 ];
        $scope.tog.modeBits = {
            owner: {
                execute: true,
                write: true,
                read: true
            },
            group: {
                execute: true,
                write: true,
                read: true
            },
            other:  {
                execute: true,
                write: true,
                read: true
            }
        }
        
        $scope.themes = ['ambiance','chaos','chrome','clouds','clouds_midnight','cobalt','crimson_editor','dawn','dreamweaver','eclipse','github','gob','gruvbox','idle_fingers','iplastic','katzenmilch','kr_theme','kuroir','merbivore','merbivore_soft','mono_industrial','monokai','pastel_on_dark','solarized_dark','solarized_light','sqlserver','terminal','textmate','tomorrow','tomorrow_night','tomorrow_night_blue','tomorrow_night_bright','tomorrow_night_eighties','twilight','vibrant_ink','xcode'];
        $scope.theme = 'monokai';
        
        editor.setTheme("ace/theme/" + $scope.theme );

        $scope.select_theme = function() {
            editor.setTheme("ace/theme/" + $scope.theme );
            $scope.flashStatusBar( "Theme changed to " + $scope.theme );
        };
        
        $scope.toggle = function( s ) {
            $scope.inputSize = $scope.$root.currentDir.length
            
            cl( s );
            if( !$scope.tog[s] )
                $scope.tog[s] = true;
            else
                $scope.tog[s] = false;
            cl($scope.tog);
        }
        
        $scope.off = function( s ) {
            $scope.tog[s] = false;
        }
        
        $scope.on = function( s ) {          
            $scope.tog[s] = true;
        }        
        
        $scope.save = function() {
            if($scope.$root.currentTab == 'new')
                $scope.toggle('newFile');
            else {
                $scope.saveFile( $scope.$root.currentTab );
            }
        }    
        
        $scope.saveDir = function() {
            //cl( "0" + $scope.modeBits[0].toString() + $scope.modeBits[1].toString() + $scope.modeBits[2].toString() )
            if( confirm( "Do you really want to create directory: " + $scope.$root.currentDir) ) {
                $http.post('index.php', {
                    saveDir: $scope.$root.currentDir,
                    mode: "0" + $scope.modeBits[0].toString() + $scope.modeBits[1].toString() + $scope.modeBits[2].toString() 
                }).then(
                    function( response ){
                        $scope.$root.loadTree();
                        $scope.off( 'newFolder' );
                        alert( response.data.status );
                });
            }
        }
        
        $scope.saveFile = function( path ) {
            if ( confirm( "Are you sure you want to save file: " + path ) ) {
                $http.post('index.php', {
                    savePath: path,
                    data: editor.getValue()
                }).then(
                    function( response ){
                        if(response.data.saved)
                            $scope.flashStatusBar( response.data.saved + " bytes saved to " + $scope.$root.currentTab );
                        else
                            $scope.flashStatusBar( "There was a problem saving the file.");
                        $scope.$root.loadTree();    
                });            
            }
        }
        
        $scope.chModeBits = function( position, value ) {
            //cl($scope.modeBits[ position ]);   
            if( $scope.modeBits[ position ] & value ) {
                $scope.modeBits[ position ] = $scope.modeBits[ position ] - value
                //cl($scope.modeBits[ position ]);   
            }
            else {
                $scope.modeBits[ position ] = $scope.modeBits[ position ] + value
                //cl($scope.modeBits[ position ]);    
            }
        }
        
        $scope.flashStatusBar = function(s){
            console.log( s );
            $scope.statusBar = s;
            bar = document.getElementById("statusBar");
            bar.classList.add("flash");
            setTimeout( function() { bar.classList.remove("flash"); }, 1000 );
        }
        
        $scope.flashStatusBar('WebDE  v0.92');
        
		// Hotkey binding TODO: there's got to be a better way
		editor.commands.addCommand({
			name: 'Save',
			bindKey: {
			    win: 'Ctrl-S',  
			    mac: 'Command-S'
			},
			exec: function( editor ) {
			    if( $scope.$root.currentTab != 'new')
				    $scope.saveFile( $scope.$root.currentTab );
			    else
			        $scope.save();
		        cl( $scope );
			},
			readOnly: false // false if this command should not apply in readOnly mode
		});        
    });
    
    app.directive('tree', function() {
    	return{
    	    controller: 'folders',
    		restrict: 'E',
    		template: '<div id="level{{depth}}"></div>'
    		    + ' <div ng-repeat="(folder, info) in $parent.node[dir].directories track by $index">'
                + ' <div class="inl" style="padding-left: {{(16 * depth) + 8}}px;">'
                + '     <i class="fa fa-plus-square pointer w3-text-green" aria-hidden="true" ng-click="getDirectoryInfo(info.path, info.path)" ng-if="info.directory && !node[info.path]"></i>'
                + '     <i class="fa fa-minus-square pointer w3-text-red" aria-hidden="true" ng-click="collapse(info.path)" ng-if="info.directory && node[info.path]"></i>'
                + '     <i class="fa fa-folder w3-text-khaki pointer aria-hidden="true" ng-click="getDirectoryInfo(info.path, info.path)" ng-if="info.directory && !node[info.path]"></i>'
                + '     <i class="fa fa-folder-open w3-text-khaki pointer aria-hidden="true" ng-if="info.directory && node[info.path]"></i>'
                + '     {{folder}}'
	            + ' </div>'
                + ' <tree dir="{{info.path}}" depth="depth + 1" a utoscroll="true"></tree>'	
	            + ' </div>'
	            + ' <div ng-repeat="(filename, info) in $parent.node[dir].files">'
                + '     <div class="inl" style="padding-left: {{(16 * depth) + 8}}px;">'
                + '         <i class="fa fa-pencil w3-text-yellow pointer tt" aria-hidden="true" ng-click="loadFile( info.path )" ng-if="!info.directory" title="edit"></i>'                
                + '         <i class="fa fa-file w3-text-blue pointer tt" aria-hidden="true" ng-click="loadFile( info.path )" ng-if="!info.directory" title="load"></i>'
                + '         <i class="fa fa-trash w3-text-red pointer tt" aria-hidden="true" ng-click="deleteFile( info.path )" ng-if="!info.directory" title="delete"></i>'
                + '         {{filename}}'
                + '     </div>'
	            + ' </div>',	            
    		scope: {
    			dir: '@',
    			depth: '='

    		},
    		link: function(scope, element, attrs) {
    		    // Tippy
                tippy('.tt');
    		    // This is necessary if loading the whole drilled down directory tree from the controller
    		    // Necessary to link up the child and parent scopes in a way that happens automatically if done from the directive.
    		    scope.node = scope.$parent.node;
    		    scope.$root.deep = 0;
    		    if( scope.depth > scope.$root.deep)
    		        scope.$root.deep = scope.depth;
            }
    	};
    });    
    
    app.directive('maButton', function() {
    	return{
    	    controller: 'ctrl',    	    
    		restrict: 'E',
    		template: '<button class="w3-button w3-round w3-{{color}}">'
    			+ '<i class="fa fa-{{tag}}" aria-hidden="true"></i> <ng-transclude></ng-transclude></button>',
    		scope: {
    			color: '@',
    			tag: '@'
    		},
    		transclude: true,
    		replace: false
    	};
    });     
    
    app.directive('tab', function() {
        return {
            controller: 'folders',
            scope: {
                color: '@',
                path: '@'
            },
            template: '<button class="w3-bar-item w3-border w3-round-large w3-button w3-text-{{color}}" ng-click="loadTab( path )">'
              + '<i class="fa fa-times-circle w3-text-light-grey w3-large" aria-hidden="true" ng-click="closeTab( path )"></i>'
              + ' <ng-transclude></ng-transclude></button>',
            transclude: true,
            replace: true
        }
    });
</script>