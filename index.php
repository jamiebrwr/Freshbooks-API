<html>
	<head>
		<title></title>
	</head>
	<body>
		<?php
			require_once('easyfreshbooks.class.php');
			$freshbooks=new easyFreshBooksAPI();
		?>
		
		 <?php
		//$page=1;
		$filters=array(
			//'per_page'=>'9999',
			//'folder' = array('active', 'archived', 'deleted)'),
		);
		
		
		$clientList = $freshbooks->clientList();
		echo '<pre>';
			var_dump($client_ids);
		echo '</pre>';
		
		
		?>
			<div class="table-responsive">
	<div id="table2_wrapper" class="dataTables_wrapper" role="grid">
		<div id="table2_length" class="dataTables_length">
			<label>
				Show 
				<select size="1" name="table2_length" aria-controls="table2">
					<option value="10" selected="selected">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
				</select>
				<div class="chosen-container chosen-container-single chosen-container-single-nosearch" style="width: 51px;" title="">
					<a class="chosen-single" tabindex="-1">
						<span>10</span>
						<div><b></b></div>
					</a>
					<div class="chosen-drop">
						<div class="chosen-search"><input type="text" autocomplete="off" readonly=""></div>
						<ul class="chosen-results"></ul>
					</div>
				</div>
				entries
			</label>
		</div>
		<div class="dataTables_filter" id="table2_filter"><label>Search: <input type="text" aria-controls="table2"></label></div>
		<table class="table dataTable" id="table2" aria-describedby="table2_info">
			<thead>
				<tr role="row">
					<th class="sorting_asc" role="columnheader" tabindex="0" aria-controls="table2" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 219px;">Rendering engine</th>
					<th class="sorting" role="columnheader" tabindex="0" aria-controls="table2" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 335px;">Browser</th>
					<th class="sorting" role="columnheader" tabindex="0" aria-controls="table2" rowspan="1" colspan="1" aria-label="Platform(s): activate to sort column ascending" style="width: 308px;">Platform(s)</th>
					<th class="sorting" role="columnheader" tabindex="0" aria-controls="table2" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending" style="width: 186px;">Engine version</th>
					<th class="sorting" role="columnheader" tabindex="0" aria-controls="table2" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 132px;">CSS grade</th>
				</tr>
			</thead>
			<tbody role="alert" aria-live="polite" aria-relevant="all">
				<?php foreach($clientList as $item){
					//print_r($item->client);
					//echo $item->client;
					$clients = $item->client;
					//echo '</pre>';	
					foreach($clients as $client_details){ ?>
				<tr class="gradeA odd">
					<td class="  sorting_1"><?php echo $client_details->client_id; ?></td>
					<td class=" "><?php echo '<a href="' . $client_details->client_id . ' ">' . $client_details->first_name . '</a>'; ?></td>
					<td class=" "><?php echo $client_details->last_name; ?></td>
					<td class="center "><?php echo $client_details->username; ?></td>
					<td class="center ">A</td>
				</tr>
				<?php } } ?>
			</tbody>
		</table>
		<div class="dataTables_info" id="table2_info">Showing 1 to 10 of 57 entries</div>
		<div class="dataTables_paginate paging_full_numbers" id="table2_paginate"><a tabindex="0" class="first paginate_button paginate_button_disabled" id="table2_first">First</a><a tabindex="0" class="previous paginate_button paginate_button_disabled" id="table2_previous">Previous</a><span><a tabindex="0" class="paginate_active">1</a><a tabindex="0" class="paginate_button">2</a><a tabindex="0" class="paginate_button">3</a><a tabindex="0" class="paginate_button">4</a><a tabindex="0" class="paginate_button">5</a></span><a tabindex="0" class="next paginate_button" id="table2_next">Next</a><a tabindex="0" class="last paginate_button" id="table2_last">Last</a></div>
	</div>
</div> <?php
		
		$invoiceList=$freshbooks->invoiceList($filters);
		
		foreach($invoiceList as $invoices){
			echo '<pre>';
				//print_r($invoices);
			echo '</pre>';
			$my_invoices = $invoices->invoice;
			
				foreach($my_invoices as $invoice){
					echo '<pre>';
						//print_r($invoices);
					echo '</pre>';
					echo $invoice->amount;
				}
		}
		
		echo '<h3>$invoiceList</h3>';			
		echo '<pre>';
			//print_r($invoiceList);
		echo '</pre>';			
						
						
						
						
						
		$filter=array(
		'per_page'=>'9999',
		/*
		'folder'=> $r = array(
			'folder' => 'deleted',
			'folder' => 'active',
		)
		*/
		);			
		$list=$freshbooks->clientList($filter);
		echo '<pre>';
			//print_r($list);
			foreach($list as $item){
			
				//print_r($item->client);
				
				//echo $item->client;
				
				$clients = $item->client;
				//echo '</pre>';	
					foreach($clients as $client_details){
						//print_r($client_detail);
						echo '<ul>';
							echo '<li>';
								echo '<strong>Client Name:</strong> ' . $client_details->first_name . ' ' . $client_details->last_name . '<br />';
								echo '<strong>Client ID:</strong> ' . $client_details->client_id . '<br />';
								echo '<strong>Email:</strong> ' . $client_details->email . '<br />';
								echo '<strong>Username:</strong> ' . $client_details->username . '<br />';
								/*
echo '<strong>Home Phone:</strong> ' . $client_details->home_phone . '<br />';
								echo '<strong>Mobile:</strong> ' . $client_details->mobile . '<br />';
								echo '<strong>Contacts:</strong> ' . $client_details->contacts . '<br />';
								echo '<strong>Organization:</strong> ' . $client_details->organization . '<br />';
								echo '<strong>Work Phone:</strong> ' . $client_details->work_phone . '<br />';
								echo '<strong>Fax:</strong> ' . $client_details->fax . '<br />';
								echo '<strong>Vat Name:</strong> ' . $client_details->vat_name . '<br />';
								echo '<strong>Vat Number:</strong> ' . $client_details->vat_number . '<br />';
								echo '<strong>Street:</strong> ' . $client_details->p_street1 . '<br />';
*/
							echo '</li>';
						echo '</ul>';
					}
					
				}
			//echo '</pre>';
		
		 ?>
	</body>
</html>