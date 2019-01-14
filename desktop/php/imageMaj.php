<?php
if (!isConnect('admin')) {
	throw new Exception(__('401 - Accès non autorisé', __FILE__));
}
?>
<div id="contenu">
	<div id="step1">
		<span class="titleStep animated slideInLeft"><i class="fas fa-hdd"></i> {{Etape 1}}</span>
		<div id="contenuWithStepOne" class="animated zoomIn contenuWith">
			<div id="contenuImage">
				<img id="contenuImageSrc" src="/core/img/imageMaj_stepUn.jpg" />
			</div>
			<div id="contenuText" class="debut">
				<span id="contenuTextSpan">Insérer une clé USB de plus de 8Go<br /> dans votre Jeedom et cliquer sur <i class="fas fa-arrow-circle-right
"></i>.</span>
				<div id="nextDiv">
					<i class="next fas fa-arrow-circle-right" id="bt_next"></i>
				</div>
			</div>
			<div id="contenuText" class="usb" style="display:none;">
		</div>
		</div>
	</div>
	<div id="step2">
		<span class="titleStep"><i class="fas fa-hdd"></i> {{Etape 2}}</span>
		<div id="contenuWithStepTwo" class="zoomIn contenuWith">
			<div id="contenuImage">
				<img id="contenuImageSrc" src="/core/img/imageMaj_stepUn.jpg" />
			</div>
			<div id="contenuText" class="backup">
				<span id="contenuTextSpan" class="TextBackup">Backup lancé merci de patienter...</span>
			<div id="contenuTextSpan" class="progress">
  <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
</div>
		</div>
		</div>
		</div>
	</div>
	<div id="step3">
		<span class="titleStep"><i class="fas fa-hdd"></i> {{Etape 3}}</span>
	</div>
	<div id="step4">
		<span class="titleStep"><i class="fas fa-hdd"></i> {{Etape 4}}</span>
	</div>
</div>
<script>
$('#bt_next').on('click', function() {
	$.ajax({
        type: 'POST',
        url: 'core/ajax/migrate.ajax.php',
        data: {
            action: 'usbTry',
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
        	$('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result){
        	var statusUsb = result.result.statut;
        	switch(statusUsb){
	        	case 'sdaNull' :
	        		alert('{{Pas de clé USB branchée}}');
	        	break;
	        	case 'sdaSup' :
	        		alert('{{Merci de branché qu\'une seul clé USB}}');
	        	break;
	        	case 'sdaNumNull' :
	        		alert('{{Merci de formaté correctement votre clé USB}}');
	        	break;
	        	case 'sdaNumSup' :
	        		alert('{{Merci de mettre une seul partition sur votre clé USB}}');
	        	break;
	        	case 'space' :
	        		alert('{{votre clé USB à un espace trop petit }}('+result.result.space+' Mo) il faut un minimum de '+result.result.minSpace+' Mo. <br />Merci');
	        	break;
	        	case 'ok' :
	        		$('.debut').hide();
					$('.usb').show();
	        		$('.usb').append('<span id="contenuTextSpan">{{Clé USB vérifié passage à l\'étape 2 en cours}}<br /><i class="next fas fa-arrow-circle-right" id="bt_next"></i></span>');
	        		setTimeout(function(){
	        			$('#step1').hide();
	        			$('#step2').show();
	        			$('#contenuWithStepTwo').addClass('animated');
	        			stepTwo();
	        		}, 4000);
	        	break;

        	}
        }
	});
});

/* FONCTION */
function stepTwo(){
	/* Lancement du backup */
	jeedom.backup.backup({
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function () {
            verifBackup();
        }
    });
}
function verifBackup(){
	$('.progress-bar').width('10%');
	$('.progress-bar').text('10%');
	getJeedomLog(1, 'backup');
}
var persiste = 0;
var netoyage = 0;
function getJeedomLog(_autoUpdate, _log) {
    $.ajax({
        type: 'POST',
        url: 'core/ajax/log.ajax.php',
        data: {
            action: 'get',
            log: _log,
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            setTimeout(function () {
                getJeedomLog(_autoUpdate, _log)
            }, 1000);
        },
        success: function (data) {
            if (data.state != 'ok') {
                setTimeout(function () {
                    getJeedomLog(_autoUpdate, _log)
                }, 1000);
                return;
            }
            var log = '';
            if($.isArray(data.result)){
                for (var i in data.result.reverse()) {
                    log += data.result[i]+"\n";
                    if(data.result[i].indexOf('[END ' + _log.toUpperCase() + ' SUCCESS]') != -1){
                        $('.TextBackup').text('Backup Fini, copie en cours sur la clé USB, ne pas debrancher celle-ci...');
                    	$('.progress-bar').width('75%');
						$('.progress-bar').text('75%');
						backupToUsb();
                        _autoUpdate = 0;
                    }else if(data.result[i].indexOf('[END ' + _log.toUpperCase() + ' ERROR]') != -1){
                        $('#div_alert').showAlert({message: '{{L\'opération a échoué}}', level: 'danger'});
                        _autoUpdate = 0;
                    }else{
	                    if(data.result[i].indexOf("Persist cache") != -1){
	                    	if(persiste == 0){
	                    		persiste = 1;
		                    	$('.TextBackup').text('Création du backup en cours...');
								$('.progress-bar').width('25%');
								$('.progress-bar').text('25%');
	                    	}
	                    }
	                    if(data.result[i].indexOf("Créer l'archive...") != -1){
	                    	var textProgress = $('.progress-bar').text();
	                    	if(netoyage == 0 && Number(textProgress.substring(0, 2)) < 70){
								$('.progress-bar').width((Number(textProgress.substring(0, 2))+1)+'%');
								$('.progress-bar').text((Number(textProgress.substring(0, 2))+1)+'%');
							}
	                    }
	                    if(data.result[i].indexOf("Nettoyage l'ancienne sauvegarde...OK") != -1){
	                    	netoyage = 1;
		                    $('.TextBackup').text('Validation du Backup...');
	                    	$('.progress-bar').width('70%');
							$('.progress-bar').text('70%');
	                    }
					}
                }
            }
            if (init(_autoUpdate, 0) == 1) {
                setTimeout(function () {
                    getJeedomLog(_autoUpdate, _log)
                }, 1000);
            }
        }
    });
}

function backupToUsb(){
	$('.progress-bar').width('80%');
	$('.progress-bar').text('80%');
	$.ajax({
        type: 'POST',
        url: 'core/ajax/migrate.ajax.php',
        data: {
            action: 'backupToUsb',
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
        	$('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result){
        	var backupToUsbResult = result.result;
        	switch(backupToUsbResult){
	        	case 'nok' :
	        		alert('{{Le backup n\a pas été copié}}');
	        	break;
	        	case 'ok' :
	        		$('.TextBackup').text('Backup Copié...');
	                $('.progress-bar').width('90%');
					$('.progress-bar').text('90%');
	        	break;
	        	default:
	        		alert(backupToUsbResult);
        	}
        }
	});
}
</script>
<?php
include_file('desktop', 'imageMaj', 'css');
?>