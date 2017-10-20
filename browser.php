<?php
/* Browzar
 * @author Robert Marin
 * @version 0.3
 */
$app = new stdClass();
$app->name = 'Browzar';
$app->version = 0.3;
$app->password = 'password';
$app->filename = basename(__FILE__);
$app->banner_msg = 'Now running on Angular!';
$app->theme_defalt = 'twilight';

// Ugly Angular Fix for seralized POST values
$_POST = json_decode(file_get_contents('php://input'),true);

// A string in $_GET superglobal to prevent unauthorized usage
if( $_GET['pw'] != $app->password )
	die("Password not entered");

// Todo:  Optimize
function sort_files( $list ) {
	$r['dirs'] = [];
	$r['files'] = [];
	$dir_count = 0;
	$file_count = 0;

	foreach( $list as $file ) {
		if( $file == '.')
			continue;
		if( is_dir( $file ) ) {
			$r['dirs'][ $dir_count ]['path'] =  getcwd();
			$r['dirs'][ $dir_count ]['name'] = $file;
			$dir_count++;
		}
		else {
			$r['files'][ $file_count ]['path'] =  getcwd();
			$r['files'][ $file_count ]['name'] = $file;
			$file_count++;
		}
	}
	return $r;
}

// Load a directory if posted.  Or load the one the file is in by default
$dir = isset( $_POST[ 'dir' ] ) ? $_POST[ 'dir' ] : './' ;
$file = $_POST[ 'file' ];
$file_list = scandir( $dir );
// Actually live in this directory to make a few things easier
chdir( $dir );
$files = sort_files( $file_list );

// Load file
if( $_POST ) {
    // Determine to load file or folder
    if( isset( $dir ) ) {
        if( isset( $file ) ) {
            echo file_get_contents( $dir . '/' . $file, true );
        }
        else {
            echo json_encode( $files );
        }
    }

    // Save file
    if( $_POST[ 'save' ] )
        echo file_put_contents( $_POST[ 'fn' ], $_POST[ 'text' ] );

    // Delete logic
    if( $_POST[ 'del' ] )
        unlink( $_POST[ 'del' ] );

    die();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/ace.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.6.4/angular.min.js"></script>
    <title><?= $app->name ?> <?= $app->version ?></title>
</head>
<body>
<div ng-app="browser" ng-controller="browserCtrl" ng-init="load_directory('./')">
    <div class="block txt-medium middle b right" id="div-status">
        <div class="block-inl"><button id='button-save' ng-click="save_file( current_filename )">Save</button></div>
        <div class="center block-inl clr-b" id='div-status-msg' ng-bind="status"></div>
    </div>
    <div class="bgg-e block-inl txt-medium overflow-scroll" id="div-menu">
        <select ng-model="theme" ng-options="option as option for option in themes" ng-click="select_theme()"></select><br/>
        <ul>
            <li>
                <div ng-if="!input_toggled">
                    <i class="fa fa-file clr-2" aria-hidden="true"></i>
                    <a class='cursor-pointer' ng-click="toggle_input()">New File</a>
                </div>
                <div ng-if="input_toggled">
                    <input type="text" placeholder="New Filename" ng-model="new_filename">
                    <a class="cursor-pointer"><i class="fa fa-floppy-o" aria-hidden="true" ng-click="new_file( new_filename )"></i></a>
                </div>
            <li>
        </ul>
        <div id='div-files'>
            <ul>
                <li ng-repeat="dir in dirs">
                    <i class="fa fa-folder clr-1" aria-hidden="true"></i>
                    <a class="cursor-pointer" ng-click="load_directory( dir.path + '/' + dir.name )">{{dir.name}}</a>
                </li>
                <li ng-repeat="file in files">
                    <i class="fa fa-file clr-2" aria-hidden="true"></i>
                    <i class="fa fa-trash clr-red cursor-pointer" aria-hidden="true" ng-click="delete_file( file.name )"></i>
                    <a class="cursor-pointer" ng-click="load_file( file.name, file.path )">{{file.name}}</a>
                </li>
            </ul>
        </div>
    </div>
    <div id="editor" ng-model="editor_text"></div>
</div>
</body>
</html>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.8/ace.js"></script>
<script>
	// Fix to automatically suppoort all available types
    var supported_types = [ 'css','html','json','php' ];
    var cwd = '<?= getcwd() ?>';
    var pass = '<?= $app->password ?>';
    var bfilename = '<?= $app->filename ?>';

	// A shorthand because god damn I use this often
    function cl(s) {
        console.log(s);
    }

	// ACE
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/ambiance");
    editor.getSession().setMode("ace/mode/javascript");

	// Angular App, Controller, and Scope
    var app = angular.module('browser', []);
    app.controller('browserCtrl', function( $scope, $http){
        $scope.status = '<?= $app->name ?> <?= $app->version ?> - <?= $app->banner_msg ?>';
        $scope.themes = ['ambiance','chaos','chrome','clouds','clouds_midnight','cobalt','crimson_editor','dawn','dreamweaver','eclipse','github','gob','gruvbox','idle_fingers','iplastic','katzenmilch','kr_theme','kuroir','merbivore','merbivore_soft','mono_industrial','monokai','pastel_on_dark','solarized_dark','solarized_light','sqlserver','terminal','textmate','tomorrow','tomorrow_night','tomorrow_night_blue','tomorrow_night_bright','tomorrow_night_eighties','twilight','vibrant_ink','xcode'];
        $scope.theme = '<?= $app->theme_defalt ?>'; // The default theme I like to use :3

        $scope.select_theme = function() {
            editor.setTheme("ace/theme/" + $scope.theme );
        };

        $scope.request = function( params, callback ) {
            $http.post( bfilename + '?pw=' + pass, params )
                .then( function( response ){
                    callback( response );
                });
        };

        $scope.save_file = function( filename, text = editor.getValue() ) {
            $scope.request(
                {   save: true,
                    fn: filename,
                    text: text
                }, function( response ) {
                    cl( response );
                });
            $scope.status = filename + ' saved';
        };

        $scope.load_file = function( filename, directory ) {
            $scope.current_filename = filename;
            if( directory )
                $scope.current_filename = directory + '/' + filename;

            if(filename.includes('.')) {
                parts = filename.split('.');
                extension = parts[parts.length - 1];
            }
            else
                extension = '';
            $scope.request( { file: filename, dir: directory }, function( response ) {
                if( supported_types.includes(extension) )
                    editor.getSession().setMode("ace/mode/" + extension);
                else
                    editor.getSession().setMode("ace/mode/text");

                editor.setValue( response.data, 1 );
                editor.clearSelection();
                $scope.status = filename + ' loaded';
            });
        }

        $scope.load_directory = function( directory ) {
            $scope.request( { dir: directory }, function( response ) {
                $scope.files = response.data.files;
                $scope.dirs = response.data.dirs;
            });
        };

        $scope.toggle_input = function() {
            $scope.input_toggled = true;
        };

        $scope.new_file = function( filename ) {
            $scope.save_file( filename, "File Created by Browser" );
            $scope.load_file( filename );
            $scope.load_directory( cwd );
        }

        $scope.delete_file = function( filename ) {
            if( confirm( "Are you sure you want to delete " + filename ) ) {
                $scope.request( { del: filename }, function( response ) {
                    cl( response );
                    $scope.load_directory( cwd );
                });
            }
        }
		
		// Hotkey binding TODO: there's got to be a better way
		editor.commands.addCommand({
			name: 'Save',
			bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
			exec: function( editor ) {
				$scope.save_file( $scope.current_filename, editor.getValue() );
			},
			readOnly: false // false if this command should not apply in readOnly mode
		});
    });
</script>