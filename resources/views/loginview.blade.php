<!doctype html>
<html lang="en">

	<head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title>Nvisionu || Quick Checkout</title>
		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="{{asset('quick-checkout/css/bootstrap.min.css')}}">
		<link rel="stylesheet" href="{{asset('quick-checkout/css/quick-checkout.css')}}">
	</head>
	
	<body class="quick_checkout-body">
		<section class="quick_checkoutMain">
			<div class="container">
				<div class="row">
					<div class="col-lg-12 col-md-12 col-sm-12">
						<div class="quick_headerLogo text-center">
							<a href="javascript:void(0)"><img src="{{asset('quick-checkout/images/brand-logo.svg')}}" alt="Branbd Logo"></a>
						</div>
					</div>
					<div class="col-lg-12 col-md-12 col-sm-12">
						<div class="quick_ctFlex">
							<div class="quick_subtotalBox">
								<div class="bag_includePrdt">
									<p>Your shopping bag includes <span class="added_prdt">{{ count($product) }} products:</span></p>
								</div>
								<table class="ckkout_prdtData">
                                    <?php // dd($product); 
                                    
                                    $subtotal = 0;
                                    $i =0;
                                    ?>
                                    
                                    @foreach ($product as  $productdata) 
                                <?php  
								 $price = $productdata['regular_product_details']['price']  ; 
								// Formatting the price to always show two decimal places
								$price = number_format($price, 2);
								   $subtotal += $price; 
								   ?>
									<tr>
										<td class="chk_prdtLeft">
											<div class="prdt_img">
												<img src="{{'https://nvisionu.corpadmin.directscale.com/CMS/Images/Inventory/'.$productdata['regular_product_details']['default_image']}}" alt="product1">
											</div>
										</td>
										<td class="chk_prdtright">
											<div class="chk_tdInner">
												<div class="prdt_titleleft">
													<h5>{{$productdata['regular_product_details']['product_name']}}</h5>
													<ul class="instock_list">
														<li>{{$user_respose['currency_symbol'].''.$price}}</li>
														<li>x1</li>
                                                        
                                                       @if($productdata['regular_product_details']['stock'] == 0)
                                                       <li class="in_stockRed text-danger">Out Of Stock</li>
                                                       @else
                                                       <li class="in_stockGreen">In Stock</li>
                                                       @endif
													   
													</ul>
												</div>
												<div class="prdt_priceRight">
                                                @if($productdata['regular_product_details']['stock'] == 0)
                                                <span>{{$user_respose['currency_symbol'].'0'}}</span>
                                                       @else
                                                       <span>{{$user_respose['currency_symbol'].''.$price}}</span>
                                                       @endif
												
												</div>
											</div>
										</td>
									</tr>
                                    @endforeach
								</table>
							 
								<table class="subTotal_Data">
									<tbody>
										<tr>
											<td class="subTitle_prdtLeft">
												<span>Subtotal:</span>
											</td>
											<td class="Total_quickprice" align="right">
												<span>  {{$user_respose['currency_symbol'].''.$subtotal}}</span>
											</td>
										</tr>
									 
									</tbody>
									<tfoot>
										<tr>
											<td class="total_tfLeft">
												<span>Total:</span>
											</td>
											<td class="total_tfRight" align="right">
											<span>  {{$user_respose['currency_symbol'].''.$subtotal}}</span>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
							<div class="quick_ckoutLogin">
								<h3>Login to checkout</h3>
								<div class="form_outer">
									<form action="{{url('loggedinuser')}}" method="post"> 
										@csrf                                                
										<div class="input-group" id="chk-admin">
											<input type="text" name="email" id="chk_email" placeholder="Your email" value="" required>
											<label id="chk_email-error" class="error"></label>
										</div>
										<input type="hidden" name="products" value="{{json_encode($product)}}">
										<div class="input-group" id="chk-pass">
											<input type="password" class="form-control" id="chk_password" name="password" placeholder="Password" required>
											<label id="chk_password-error" class="error"></label>
											<div class="component_chk" id="chk_hide-password" style="display: none;">
												<img src="{{asset('quick-checkout/images/hide.svg')}}" alt="Hide login password">
											</div>
											<div class="component_chk" id="chk_show-password">
												<img src="{{asset('quick-checkout/images/view.svg')}}"  alt="Show login password">
											</div>
										</div>
										<div class="input-group chk-forgot">
											<a href="javascript:void(0)" class="forgotpslink">Forgot password?</a>
										</div>
										<div class="submitbtn_chk">
											<button id="submitbtn_chk" type="submit" class="submit-btnchk">log in</button>
										</div>
									</form>
									
								</div>
								@if($errors->any())
									<div class="alert alert-danger">
										<ul>
											@foreach ($errors->all() as $error)
											<li>{{ $error }}</li>
											@endforeach
										</ul>
									</div>
									@endif
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	
		
		<!-- Scripts Start Here -->
		<script src="{{asset('quick-checkout/js/jquery-3.7.1.min.js')}}"></script>
		<script src="{{asset('quick-checkout/js/bootstrap.min.js')}}"></script>
		<!-- Scripts End Here -->
		<script>
		$(document).ready(function() {
			$("#chk_show-password").click(function() {
        var passwordField = $("#chk_password");
		passwordField.attr("type", 'text');
			$("#chk_hide-password").show()
			$("#chk_show-password").hide()
    });
	$("#chk_hide-password").click(function() {
        var passwordField = $("#chk_password");
		passwordField.attr("type", 'password');
			$("#chk_hide-password").hide()
			$("#chk_show-password").show()
    });
});

		</script>
	</body>
</html>