
<body onload="">
	<div>
		<link href="../modules/webpay/views/css/tbk.css" rel="stylesheet">
		<script src="../modules/webpay/views/js/request.js"> </script>

		<h2>{l s='Pago electrónico con Tarjetas de Crédito o Redcompra a través de Webpay Plus' mod='webpay'}</h2>
		<button  class ="btn btn-primary" data-toggle="modal" data-target="#tb_modal">Información</button>
		<hr>

		<form action="{$post_url|escape:'htmlall':'UTF-8'}" method="post" style="clear: both; margin-top: 10px;">

			<h2 class="">{l s='Configuración' mod='webpay'}</h2>
			{if isset($errors.merchantERR)}
				<div class="error">
					<p>{$errors.merchantERR|escape:'htmlall':'UTF-8'}</p>
				</div>
			{/if}

			<label for="storeID">{l s='Código de Comercio' mod='webpay'}</label>
			<div class="margin-form"><input type="text" size="90" id="storeID" name="storeID" value="{$data_storeid|escape:'htmlall':'UTF-8'}"/></div>
			<br/>
			<label for="secretCode">{l s='Llave privada' mod='webpay'}</label>
			<div class="margin-form"><textarea style="font-family: monospace" cols="90" rows="6" wrap="soft" placeholder="" name="secretCode" id="secretCode" value="{$data_secretcode|escape:'htmlall':'UTF-8'}">{$data_secretcode|escape:'htmlall':'UTF-8'}</textarea></div>
			<br/>
			<label for="certificate">{l s='Certificado' mod='webpay'}</label>
			<div class="margin-form"><textarea style="font-family: monospace" cols="90" rows="6" wrap="soft" id="certificate" name="certificate" value="{$data_certificate|escape:'htmlall':'UTF-8'}"/>{$data_certificate|escape:'htmlall':'UTF-8'}</textarea></div>
			<br/>
			<label for="ambient">{l s='Ambiente' mod='webpay'}</label>
			<div class="margin-form">
				<select name="ambient" onChange="
					if(this.options[0].selected){
						cargaDatosIntegracion();
					}else if(this.options[1].selected){
						cargaDatosProduccion();
					}" default="INTEGRACION">
					<option value="INTEGRACION" {if $data_ambient eq "INTEGRACION"}selected{/if}>Integración</option>
					<option value="PRODUCCION" {if $data_ambient eq "PRODUCCION"}selected{/if}>Producción</option>
				</select>
			</div>
			<br/>

            <label for="default_order_status_after_payment">{l s='Estado del pedido despues del pago' mod='webpay'}</label>
            <div class="margin-form">
                 <select name="webpay_default_order_state_id_after_payment">
                    {foreach from=$payment_states key=index item=status_data}
                        {if strval($status_data['id_order_state']) == strval($default_after_payment_order_state_id) }
                            <option selected value="{$status_data['id_order_state']}">
                                {$status_data["name"]}
                            </option>
                        {else}
                            <option value="{$status_data['id_order_state']}">
                                {$status_data["name"]}
                            </option>
                        {/if}
                    {/foreach}
                </select>
            </div>
            <br/>

			<div align="right">
				<button type="submit" value="1" id="webpay_updateSettings" name="webpay_updateSettings" class="btn btn-info pull-right">
					<i class="process-icon-save" value="{l s='Save Settings' mod='webpay'}"></i> Guardar
				</button>
			</div>

		</form>

		<div class="modal" id="tb_modal">
			<div class="modal-dialog" >
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<ul class="nav nav-tabs">
							<li class="active" > <a data-toggle="tab" href="#info" class="tbk_tabs">{l s='Información' mod='webpay'}</a></li>
							<li> <a data-toggle="tab" href="#php_info" class="tbk_tabs">{l s='PHP info' mod='webpay'}</a></li>
							<li> <a data-toggle="tab" href="#logs" class="tbk_tabs">{l s='logs' mod='webpay'}</a></li>
						</ul>
					</div>
					<div class="modal-body">
						<div class="tab-content">

							<!-- INFORMACION -->
							<div id="info" class="tab-pane fade in active">

								<h2 class="">{l s='Informe pdf' mod='webpay'}</h2>
								<form method="post" action="../modules/webpay/libwebpay/CreatePdf.php" target="_blank">
									<input type="hidden" name="ambient" value="{$data_ambient}">
									<input type="hidden" name="storeID" value="{$data_storeid}">
									<input type="hidden" name="certificate" value="{$data_certificate}">
									<input type="hidden" name="secretCode" value="{$data_secretcode}">
									<input type="hidden" name="document" value="report">
									<button type = "submit" class="btn btn-primary">
										<i class="icon-file-text" value="{l s='genera pdf' mod='webpay'}"></i> Crear PDF
									</button>
								</form>

								<hr style="border-width: 2px">

								<h2 class="">{l s='Información de Plugin / Ambiente' mod='webpay'}</h2>
								<table class="table table-striped">
									<tr>
										<td><div title="Nombre del E-commerce instalado en el servidor" class="label label-info">?</div> <strong>{l s='Software E-commerce' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$ecommerce}</td>
									</tr>
									<tr>
										<td><div title="Versión de {$ecommerce} instalada en el servidor" class="label label-info">?</div> <strong>{l s='Version E-commerce' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$ecommerce_version}</td>
									</tr>
									<tr>
										<td><div title="Versión del plugin Webpay para {$ecommerce} instalada actualmente" class="label label-info">?</div> <strong>{l s='current_plugin_version' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$current_plugin_version}</td>
									</tr>
									<tr>
										<td><div title="Última versión del plugin Webpay para {$ecommerce} disponible" class="label label-info">?</div> <strong>{l s='last_plugin_version' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$last_plugin_version}</td>
									</tr>
								</table>

								<br>
								<h2 class="">{l s='Validación certificados' mod='webpay'}</h2>
								<h4 class="tbk_table_title">{l s='Consistencias' mod='webpay'}</h4>
								<table class="table table-striped">
									<tr>
										<td><div title="Informa si las llaves ingresadas por el usuario corresponden al certificado entregado por Transbank" class="label label-info">?</div> <strong>{l s='Consistencias con llaves' mod='webpay'}: </strong></td>
										<td class="tbk_table_td"><span  class="label {if $cert_vs_private eq 'OK'}label-success2{else}label-danger2{/if}">{$cert_vs_private}</span></td>
									</tr>
									<tr>
										<td><div title="Informa si el código de comercio ingresado por el usuario corresponde al certificado entregado por Transbank" class="label label-info">?</div> <strong>{l s='Validación Código de comercio' mod='webpay'}: </strong></td>
										<td class="tbk_table_td"><span class="label {if $commerce_code_validate eq 'OK'}label-success2{else}label-danger2{/if}">{$commerce_code_validate}</span></td>
									</tr>
								</table>

								<h4 class="tbk_table_title">{l s='cert_info' mod='webpay'}</h4>
								<table class="table table-striped">
									<tr>
										<td><div title="CN (common name) dentro del certificado, en este caso corresponde al código de comercio emitido por Transbank" class="label label-info">?</div> <strong>{l s='Código de Comercio Válido' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$subject_commerce_code}</td>
									</tr>
									<tr>
										<td><div title="Versión del certificado emitido por Transbank" class="label label-info">?</div> <strong>{l s='Versión certificado' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$cert_version}</td>
									</tr>
									<tr>
										<td><div title="Informa si el certificado está vigente actualmente" class="label label-info">?</div> <strong>{l s='Vigencia' mod='webpay'}: </strong></td>
										<td class="tbk_table_td"><span  class="label {if $cert_is_valid eq 'OK'}label-success2{else}label-danger2{/if}">{$cert_is_valid}</span></td>
									</tr>
									<tr>
										<td><div title="Fecha desde la cual el certificado es válido" class="label label-info">?</div> <strong>{l s='valid_from' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$valid_from}</td>
									</tr>
									<tr>
										<td><div title="Fecha hasta la cual el certificado es válido" class="label label-info">?</div> <strong>{l s='valid_to' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$valid_to}</td>
									</tr>
								</table>

								<br>
								<h2 class="">{l s='php_extensions_status' mod='webpay'}</h2>
								<h4 class="tbk_table_title">{l s='Información Principal' mod='webpay'}</h4>
								<table class="table table-striped">
									<tr>
										<td><div title="Descripción del Servidor Web instalado" class="label label-info">?</div> <strong>{l s='Software Servidor' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$server_version}</td>
									</tr>
								</table>

								<h4 class="tbk_table_title">{l s='PHP' mod='webpay'}</h4>
								<table class="table table-striped">
									<tr>
										<td><div title="Informa si la versión de PHP instalada en el servidor es compatible con el plugin de Webpay" class="label label-info">?</div> <strong>{l s='Estado de PHP' mod='webpay'}</strong></td>
										<td class="tbk_table_td"><span  class="label {if $php_status eq 'OK'}label-success2{else}label-warning{/if}">{$php_status}</td>
									</tr>
									<tr>
										<td><div title="Versión de PHP instalada en el servidor" class="label label-info">?</div> <strong>{l s='version' mod='webpay'}: </strong></td>
										<td class="tbk_table_td">{$php_version}</td>
									</tr>
								</table>

								<h4 class="tbk_table_title">{l s='Extensiones PHP requeridas' mod='webpay'}</h4>
								<table class="table table-responsive table-striped">
									<tr>
										<th>{l s='Extensión' mod='webpay'}</th>
										<th>{l s='Estado' mod='webpay'}</th>
										<th class="tbk_table_td">{l s='Versión' mod='webpay'}</th>
									</tr>
									<tr>
										<td style="font-weight:bold">{l s='openssl' mod='webpay'}</td>
										<td> <span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$openssl_status}</span></td>
										<td class="tbk_table_td">{$openssl_version}</td>
									</tr>
									<tr>
										<td style="font-weight:bold">{l s='SimpleXml' mod='webpay'}</td>

										<td> <span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$SimpleXML_status}</span></td>
										<td class="tbk_table_td">{$SimpleXML_version}</td>
									</tr>
									<tr>
										<td style="font-weight:bold">{l s='soap' mod='webpay'}</td>
										<td><span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$soap_status}</span></td>
										<td class="tbk_table_td">{$soap_version}</td>
									</tr>
									<tr>
										<td style="font-weight:bold">{l s='dom' mod='webpay'}</td>
										<td><span class="label {if $openssl_status eq 'OK'}label-success2{else}label-danger2{/if}">{$dom_status}</span></td>
										<td class="tbk_table_td">{$dom_version}</td>
									</tr>
								</table>

								<br>
								<h2 class="">{l s='Validación Transacción' mod='webpay'}</h2>
								<h4 class="tbk_table_title">{l s='Petición a Transbank' mod='webpay'}</h4>
								<table class="table table-striped">
									<tr>
										<td class="tbk_table_td"> <button class="check_conn btn btn-sm btn-primary">Verificar Conexión</button>  </td>
									</tr>
								</table>
								<hr>
								<h4 class="tbk_table_title">{l s='Respuesta de Transbank' mod='webpay'}</h4>
								<table class="table table-striped">
									<tr id="row_response_status" style="display:none">
										<td><div title="Informa el estado de la comunicación con Transbank mediante método init_transaction" class="label label-info">?</div> <strong>{l s='status' mod='webpay'}: </strong></td>
										<td><span class="status-label label" style="display:none"></span></td>
									</tr>
									<tr id="row_response_url" style="display:none">
									   <td><div title="URL entregada por Transbank para realizar la transacción" class="label label-info">?</div> <strong>{l s='URL' mod='webpay'}: </strong></td>
									   <td class="tbk_table_trans content_url"></td>
									</tr>
									<tr id="row_response_token" style="display:none">
									   <td><div title="Token entregada por Transbank para realizar la transacción" class="label label-info">?</div> <strong>{l s='Token' mod='webpay'}: </strong></td>
									   <td class="tbk_table_trans content_token"></td>
									</tr>
									<tr id="row_error_message" style="display:none">
										<td><div title="Mensaje de error devuelto por Transbank al fallar init_transaction" class="label label-info">?</div> <strong>{l s='Error' mod='webpay'}: </strong></td>
										<td class="tbk_table_trans error_content"></td>
									</tr>
									<tr id="row_error_detail" style="display:none">
										<td><div title="Detalle del error devuelto por Transbank al fallar init_transaction" class="label label-info">?</div> <strong>{l s='Detalle' mod='webpay'}: </strong></td>
										<td class="tbk_table_trans error_detail_content"></td>
									</tr>

								</table>
							</div>

							<!-- PHP INFO -->
							<div id="php_info" class="tab-pane fade">
								<h2 class="">{l s='Informe PHP info ' mod='webpay'}</h2>
								<form method="post" action="../modules/webpay/libwebpay/CreatePdf.php" target="_blank">
									<input type="hidden" name="ambient" value="{$data_ambient}">
									<input type="hidden" name="storeID" value="{$data_storeid}">
									<input type="hidden" name="certificate" value="{$data_certificate}">
									<input type="hidden" name="secretCode" value="{$data_secretcode}">
									<input type="hidden" name="document" value="php_info">
									<button type = "submit" class="btn btn-primary">
										<i class="icon-file-text" value="{l s='Crear PHP info' mod='webpay'}"></i> Descargar PDF con PHP info
									</button>
								</form>

								<hr style="border-width: 2px">

								<br>
								<span style="font-size: 10px; font-family:monospace; display: block; background: white;overflow: hidden; width: 90%;" >{$php_info}</span><br>
							</div>

							<!-- REGISTROS -->
							<div id="logs" class="tab-pane fade">

								<h2 class="">{l s='Información de Registros' mod='webpay'}</h2>

								<table class="table table-striped">
									<tr style="display: none;">
										<td><div title="Informa si actualmente se guarda la información de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Estado de Registros" mod='webpay'}: </strong></td>
										<td class="tbk_table_td"><span id="action_txt" class="label label-success2">{l s='Registro-activado' mod='webpay' }</span><br> </td>
									</tr>
									<tr>
										<td><div title="Carpeta en el servidor en donde se guardan los archivos con la informacón de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Directorio de registros" mod='webpay'}: </strong></td>
										<td class="tbk_table_td td_log_dir">{$log_dir}</td>
									</tr>
									<tr>
										<td><div title="Cantidad de archivos que guardan la información de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Cantidad de Registros en Directorio" mod='webpay'}: </strong></td>
										<td class="tbk_table_td td_log_count">{$logs_count} </td>
									</tr>
									<tr>
										<td><div title="Lista los archivos archivos que guardan la información de cada compra mediante Webpay" class="label label-info">?</div> <strong>{l s="Listado de Registros Disponibles" mod='webpay'}: </strong></td>
										<td class="tbk_table_td td_log_files">
											<ul style="font-size:0.8em;">
												{foreach from=$logs_list item=index}
													<li>{$index}</li>
												{/foreach}
											</ul>
										</td>
									</tr>
								</table>

								<h2 class="">{l s='Últimos Registros' mod='webpay'}</h2>

								<table class="table table-striped">
									<tr>
										<td><div title="Nombre del útimo archivo de registro creado" class="label label-info">?</div> <strong>{l s="Último Documento" mod='webpay'}: </strong></td>
										<td class="tbk_table_td td_log_last_file">{$log_file} </td>
									</tr>
									<tr>
										<td><div title="Peso del último archivo de registro creado" class="label label-info">?</div> <strong>{l s="Peso del Documento" mod='webpay'}: </strong></td>
										<td class="tbk_table_td td_log_file_weight">{$log_weight}</td>
									</tr>
									<tr>
										<td><div title="Cantidad de líneas que posee el último archivo de registro creado" class="label label-info">?</div> <strong>{l s="Cantidad de Líneas" mod='webpay'}: </strong></td>
										<td class="tbk_table_td td_log_regs_lines">{$log_regs_lines} </td>
									</tr>
								</table>
								<br>
								<pre>
									<span class="log_content" style="font-size: 10px; font-family:monospace; display: block; background: white;width: fit-content;" >{$logs}</span>
								</pre>

							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>

        <script type="text/javascript">

			function updateConfigLogs(){
				$.ajax({
					type:'POST',
                    url:'../modules/webpay/libwebpay/UpdateConfigLog.php',
                    dataType:'json',
					data: {
						status: document.getElementById("action_check").checked,
						max_days: $("#days").val(),
						max_weight: $("#size").val()
					},
					success:function(response) {
					}
				});
			}

			function cargaDatosIntegracion(){
				var private_key_js = "{$data_secretcode_init}".replace(/<br\s*\/?>/mg,"\n");
				var public_cert_js = "{$data_certificate_init}".replace(/<br\s*\/?>/mg,"\n");
				document.getElementById('storeID').value = "{$data_storeid_init|escape:'htmlall':'UTF-8'}";
				document.getElementById('secretCode').value = private_key_js;
				document.getElementById('certificate').value = public_cert_js;
			}

			function cargaDatosProduccion(){
				document.getElementById('secretCode').value = '';
				document.getElementById('certificate').value = '';
				document.getElementById('storeID').value = '';
			}

		</script>
	</div>
</body>
