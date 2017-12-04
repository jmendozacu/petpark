<?php

$testa = $_POST['veio'];


if($testa != "") {
	
	$nome = $_POST['nome'];
	$de = $_POST['de'];
	$to = $_POST['emails'];
	$headers  = "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
	$email = explode("\n", $to);
	$headers .= "From: ".$nome." <".$de.">\r\n";
	$i = 0;
	$count = 1;
while($email[$i]) {
	
	$message = $_POST['html'];
	
	$message = stripslashes($message);
	
	$numerorand = rand(1111111111,9999999999);
				
	$arrMail = explode("@",trim($email[$i]));
				
	$subject = $_POST['assunto'];
	
	$subject = str_replace('{email}',$arrMail[0],$subject);
				
	$subject = str_replace('{codigo}',md5(time().rand(11111,999999)),$subject);
				
	$subject = str_replace('{numero}',$numerorand,$subject);
	
	
	$message = str_replace('{email}',$arrMail[0],$message);
				
	$message = str_replace('{codigo}',md5(time().rand(11111,999999)),$message);
				
	$message = str_replace('{numero}',$numerorand,$message);

				

$ok = "ok";
if(mail($email[$i], $subject, $message, $headers))
echo "* Numero: $count <b>".$email[$i]."</b> <font color=green>Enviado</font><br><hr>";
else
echo "* Numero: $count <b>".$email[$i]."</b> <font color=red>ERRO AO ENVIAR!</font><br><hr>";
$i++;
$count++; 
}
$count--;
if($ok == "ok")
echo " <font color=red>ENVIO TERMINADO</font><br><hr>";
}
?>
<html>
<head>
<title>GORDO 2014</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style>
body {
	margin-left: 0;
	margin-right: 0;
	margin-top: 0;
	margin-bottom: 0;
}
.titulo {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 70px;
	color: #000000;
	font-weight: bold;
}

.normal {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #000000;
}

.form {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: #333333;
	background-color: #FFFFFF;
	border: 1px dashed #666666;
}

.texto {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
}

.alerta {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color: #990000;
	font-size: 10px;
}
</style>
</head>
<body>
<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
  <input type="hidden" name="veio" value="sim">
  <table width="464" height="511" border="0" cellpadding="0" cellspacing="1" 

bgcolor="#D3D3D3" class="normal">
    <tr>
      <td width="462" height="25" align="center" bgcolor="#D3D3D3"><span class="titulo">GORDO2014</span></td>
    </tr>
    <tr>
      <td height="194" valign="top" bgcolor="#FFFFFF">
	  	<table width="100%"  border="0" cellpadding="0" cellspacing="5" 

class="normal" height="444">
		  <tr>
            <td align="right" height="17"><span class="texto">De (Nome)/(E-Mail) 

:</span></td>
            <td width="65%" height="17"><input name="nome" value="Finaceiro" type="text" 

class="form" id="nome" style="width:48%" > 
            <input name="de" value="" 

type="text" class="form" id="de" style="width:49%" ></td>
          </tr>
          <tr>
            <td align="right" height="17"><span class="texto">Assunto:</span></td>
            <td height="17"><input name="assunto" type="text" value="Conforme solicitado, o compravante de depois. {numero}"class="form" id="assunto" style="width:100%" ></td>
          </tr>
          <tr align="center" bgcolor="#D3D3D3">
            <td height="20" colspan="2" bgcolor="#D3D3D3"><span class="texto">C&oacute;digo 

HTML:</span></td>
          </tr>
          <tr align="right">
            <td height="146" colspan="2" valign="top"><br>
             <textarea name="html" style="width:100%" rows="8" wrap="VIRTUAL" class="form" 

id="html">

</textarea>
              <span class="alerta">*Lembrete: texto em HTML</span></td>



          </tr>
          <tr align="center" bgcolor="#D3D3D3">
            <td height="47" colspan="2"><span class="texto">Coloque os emails abaixo 

abaixo: </span>
              <p><span class="texto">OBS: um e-mail em cima do outro </span></td>
          </tr>
          <tr align="right">
            <td height="136" colspan="2" valign="top"><br>
              <textarea name="emails" style="width:100%" rows="8" wrap="VIRTUAL" class="form" 

id="emails"></textarea>
              <span class="alerta">*Separado por quebra de linha</span> </td>
          </tr>
          <tr>
            <td height="26" align="right" valign="top" colspan="2"><input type="submit" 

id="bota" name="Submit" value="Enviar"></td>
          </tr>
        </table>
	  </td>
    </tr>
    <tr>
      <td height="15" align="center" bgcolor="#D3D3D3">&nbsp;</td>
    </tr>
  </table>
</form>
</body>