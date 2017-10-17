<?php
/*
 * @author Robert Marin
 * @version 0.1
 *
 */

// A string in $_GET superglobal to prevent unauthorized usage
// use 'yoururl?pw=password'
// change below
if( $_GET['pw'] != 'password')
	die("Password not entered");

function sort_files( $list ) {
    $r['dirs'] = [];
    $r['files'] = [];
	$a = 0;
	$b = 0;

	foreach( $list as $file ) {
	    if( $file == '.')
            continue;
		if( is_dir( $file ) ) {
			$r['dirs'][ $a ]['path'] =  getcwd();
			$r['dirs'][ $a ]['name'] = $file;
			$a++;
		}
		else {
			$r['files'][ $b ]['path'] =  getcwd();
			$r['files'][ $b ]['name'] = $file;
			$b++;
        }
	}
	return $r;
}

// Load file
if( $_POST[ 'file' ] ) {
	if( $_POST[ 'dir' ] )
		$path = $_POST[ 'dir' ] . '/';
	echo file_get_contents( $path . $_POST[ 'file' ], true );
	die();
}

// Save file
if( $_POST[ 'save' ] ) {
	echo file_put_contents( $_POST[ 'fn' ], $_POST[ 'text' ] );
	die();
}

// Load a directory if posted.  Or load the one the file is in by default
$dir_posted = isset( $_POST[ 'dir' ] );
$path = $dir_posted ? $_POST[ 'dir' ] : './';
$file_list = scandir( $path );

if( $dir_posted )
	chdir( $_POST['dir'] );

$files = sort_files( $file_list );
if( $dir_posted ) {
    echo json_encode( $files );
    die();
}

$themes = ['ambiance','chaos','chrome','clouds','clouds_midnight','cobalt','crimson_editor','dawn','dreamweaver','eclipse','github','gob','gruvbox','idle_fingers','iplastic','katzenmilch','kr_theme','kuroir','merbivore','merbivore_soft','mono_industrial','monokai','pastel_on_dark','solarized_dark','solarized_light','sqlserver','terminal','textmate','tomorrow','tomorrow_night','tomorrow_night_blue','tomorrow_night_bright','tomorrow_night_eighties','twilight','vibrant_ink','xcode'];
?>

<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/ace.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>

    </style>
</head>
<div class="block txt-medium middle b right" id="div-status">
    <div class="block-inl"><button id='button-save'>Save</button></div>
    <div class="center block-inl clr-b" id='div-status-msg'>Welcome to Browser 0.1</div>
</div>
<div class="bgg-e block-inl txt-medium overflow-scroll" id="div-menu">
    <select id='select-theme'>
		<?php foreach( $themes as $theme ): ?>
            <option><?= $theme ?></option>
		<?php endforeach; ?>
    </select><br/>
    <ul>
        <li>
            <div id='div-new-file'>
                <i class="fa fa-file clr-2" aria-hidden="true"></i>
                <a class='cursor-pointer'  id='link-new-file'>New File</a>
            </div>
        <li>
    </ul>
    <div id='div-files'>
        <ul>
			<?php foreach($files['dirs'] as $file): ?>
            <li>
                <i class="fa fa-folder clr-1" aria-hidden="true"></i>
                <a class='cursor-pointer' onclick=load_dir('<?= $file['name'] ?>')><?= $file['name'] ?></a>
            </li>
			<?php endforeach; ?>
			<?php foreach($files['files'] as $file): ?>
            <li>
                <i class="fa fa-file clr-2" aria-hidden="true"></i>
                <a class='cursor-pointer' onclick=load_file('<?= $file['name'] ?>','<?= getcwd() ?>')><?= $file['name'] ?></a>
            </li>
			<?php endforeach; ?>
        </ul>
    </div>
</div>
<div id="editor"></div>
</html>

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.8/ace.js"></script>
<script>
    var current_filename = '';
    var supported_types = [ 'css','html','json','php' ];

    $('#select-theme').change( function() {
        theme = $('#select-theme').val();
        editor.setTheme("ace/theme/" + theme );
    });

    $('#button-save').click( function() {
        save_file( current_filename, editor.getValue() );
    })

    $('#link-new-file').click( function() {
        $('#div-new-file')
            .html('<input type="text" id="text-new-file" placeholder="New Filename">')
            .append('<a class="cursor-pointer" id="link-file-save"><i class="fa fa-floppy-o" aria-hidden="true"></i></a>');

        $('#link-file-save').click( function() {
            new_file( $('#text-new-file').val() );
        });
    });

    function cl(s) {
        console.log(s);
    }

    function load_file( file, dir ) {
        if( dir )
            current_filename = dir + '/' + file;
        else
            current_filename = file;

        cl(current_filename);
        if(file.includes('.')) {
            parts = file.split('.')
            extension = parts[parts.length - 1];
        }
        else
            extension = '';

        $('document').ready( function() {
            $.post( "browser.php?pw=triple3", { file: file, dir: dir } )
                .always( function() {
                    write_status('Loading File: ' + file);
                })
                .done( function( data ) {
                    if( supported_types.includes(extension) )
                        editor.getSession().setMode("ace/mode/" + extension);
                    else
                        editor.getSession().setMode("ace/mode/text");

                    editor.setValue(data, 1);
                    editor.clearSelection();
                    write_status( file + ' loaded');
                });
        });
    };

    function save_file( fn, text ) {
        console.log(fn);
        $('document').ready( function() {
            $.post( "browser.php?pw=triple3", {
                fn: fn,
                save: true,
                text: text
            })
                .fail( function( data ) {
                    write_status('There was an error saving this file');
                })
                .always( function() {
                    write_status('Saving');
                })
                .done( function( data ) {
                    write_status('Saved');
                    load_file( fn );
                });
        });
    }

    function load_dir( dir ) {
        $.post( "browser.php?pw=triple3", { dir: dir } )
            .always( function() {
                write_status('Loading Directory: ' + dir);
            })
            .done( function( data ) {
                list = JSON.parse(data);
				console.log(list);
                $('#div-files').html("");
                $.each( list.dirs, function(i, v) {
                    $('#div-files').append( '<i class="fa fa-folder clr-1" aria-hidden="true"></i>' );
                    $('#div-files').append( '<a onclick=load_dir(\'' + v.path + '/' + v.name + '\') class="cursor-pointer"> ' + v.name + "<br/>" );
                });
                $.each( list.files, function(i, v) {
                    $('#div-files').append( '<i class="fa fa-file clr-2" aria-hidden="true"></i>' );
                    $('#div-files').append( '<a onclick=load_file(\'' + v.name + '\',\'' + v.path + '\') class="cursor-pointer"> ' + v.name + "<br/>" );
                });
            });
    }

    function write_status( message ) {
        $('#div-status-msg').html( message );
    }

    function new_file( fn ) {
        console.log( fn );
        save_file( fn, "New File Created by Browzar" );
    }

    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/ambiance");
    editor.getSession().setMode("ace/mode/javascript");

    editor.commands.addCommand({
        name: 'Save',
        bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
        exec: function(editor) {
            save_file( current_filename, editor.getValue() );
        },
        readOnly: true // false if this command should not apply in readOnly mode
    });
</script>