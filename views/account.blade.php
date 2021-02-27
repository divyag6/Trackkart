@extends('app') @section('sidebar') @include('includes.sidebar') @stop

@section('content')
<div class="col-md-9">
	@if(isset($message))
		@include('includes.message');
	@else
		@include('errors.list')
		<div class="panel panel-info">
		<div class="panel-heading">Orders Placed.</div>
			<div class="table-responsive">
				<table id="table" class="table tablesorter-bootstrap"
					style="right-padding: 0;">
					<thead>
						<tr>
							<th class="text-center filter-select col-md-1" data-placeholder="All vendors">Vendor</th>
							<th class="text-center col-md-1" data-placeholder="Show all">Date</th>
							<th class="text-center col-md-2" data-placeholder="Enter Order No.">Order No.</th>
							<th class="text-center col-md-1" data-placeholder="Show all">Amount &#8377;</th>
							<th class="text-center filter-false col-md-4">Item Description</th>
							<th class="text-center filter-false col-md-1">Qty</th>
							<th class="text-center filter-false col-md-2" >Status</th>
						</tr>
						
					</thead>
					<tbody>
						@foreach($orders as $order)
						
						<tr>
								
							<td class="text-center">
								<a href ="{{array_has($order,'link')?$order['link']:'#'}}" target="_blank">
									{{array_has($order,'vendor')?$order['vendor']:""}}
								</a>
							</td>
							<td class="text-center">{{array_has($order,'date')?$order['date']:""}}</td>
							<td class="text-center">{{array_has($order,'order_no')?$order['order_no']:""}}</td>
							@if(isset($order['item_count']) && $order['item_count']!=1)
							
							<td class="text-center">
								@if(array_has($order,'paid')) 
									{{$order['paid']}}
								@else 
									@for($row=1;$row<$order['item_count'];$row++)
										
										{{array_has($order[$row],'item_price') ? $order[$row]['item_price'] :""}} <br><br>
									
									@endfor
								@endif
							
							</td>
							<td class="text-center">							
								@for($row=1;$row<$order['item_count'];$row++)
	
								{{array_has($order[$row],'item') ? $order[$row]['item'] :""}} <br><br>
	
	
								@endfor
							</td>
							<td class="text-center">
								@if(array_has($order,'qty')) 
									{{$order['qty']}}
								@else
									@for($row=1;$row<$order['item_count'];$row++)
	
										{{array_has($order[$row],'qty')? $order[$row]['qty']:""}} <br><br>
									@endfor
								@endif
							</td>
							<td class="text-center">
								@for($row=1;$row<$order['item_count'];$row++)
	
								{{array_has($order[$row],'shipment_status')?
								$order[$row]['shipment_status']:""}} <br>
								{{array_has($order[$row],'delivered_on')?
								$order[$row]['delivered_on']:""}} <br> 
								
								@endfor
							</td>											
							
							
							
							
							@endif
							
						</tr>
						
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	@endif
</div>
@stop
