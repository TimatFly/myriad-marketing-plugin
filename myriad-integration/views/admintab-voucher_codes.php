<?php
global $viewData;
extract($viewData);
?>

<h2>Myriad Voucher Code Options</h2>

<div class="wrap">
	<?php if($page_state == "select_voucher"): ?>
		<h3>Select Voucher Code</h3>

		<ul>
			<?php if(count($groups)): ?>
				<?php foreach($groups as $group_id => $group_name): ?>
					<li><a href="/wp-admin/options-general.php?page=myriad-voucher-code&state=view_voucher&voucher_id=<?php print $group_id; ?>"><?php print $group_name; ?></a> [<a href="/wp-admin/options-general.php?page=myriad-voucher-code&state=remove&voucher_id=<?php print $group_id; ?>">delete</a>]</li>
				<?php endforeach; ?>
			<?php endif; ?>
			<li>&nbsp;</li>
			<li><a href="/wp-admin/options-general.php?page=myriad-voucher-code&state=create_voucher">Create new voucher group</a></li>
		</ul>
	<?php elseif($page_state == "create_voucher"): ?>
		<form method="post" action="/wp-admin/options-general.php?page=myriad-voucher-code&state=create_voucher"> 
			<div class="input-group">
				<label for="group_name">Group Name</label>
				<input name="group_name" value="" required="required" />
			</div>
			<div class="input-group">
				<label for="product_id">Product</label>
				<?php
				    $product_args = array( 'post_type' => 'product', 'posts_per_page' => 10000);
				    $products = new WP_Query($product_args);
				?>
				<select name="product_id">
				    <?php while ( $products->have_posts() ) : $products->the_post(); ?>
				    	<option value="<?php print $products->post->ID ?>"><?php print $products->post->post_title ?></option>
				    <?php endwhile; ?>
				</select>
			</div>
			<div class="input-group">
				<label for="discount">Discount</label>
				<input name="discount" value="" required="required" />
			</div>
			<div class="input-group">
				<label for="product_id">Payment Type</label>
				<select name="payment_type">
				    <?php foreach($payment_types as $payment_type_id => $payment_type): ?>
				    	<option value="<?php print $payment_type_id ?>"><?php print $payment_type ?></option>
				    <?php endforeach; ?>
				</select>
			</div>

			<h3>Landing page options</h3>
			<div class="input-group">
				<label for="group_landing">Landing page link</label>
				<input name="group_landing" value="" />
				<p>Enter landing page path if this should link to a landing page. You will get a &lt;?php $_GET['coupon_code_link']; ?&gt; to add the product to the cart</p>
			</div>
			<button type="submit">Save</button>
		</form>
	<?php elseif($page_state == "remove"): ?>
		<p>Are you sure you wish to remove the voucher group?<br /><br /><a href="/wp-admin/options-general.php?page=myriad-voucher-code&state=remove&voucher_id=<?php print $_GET['voucher_id']; ?>&status=submit">Yes</a> <a href="/wp-admin/options-general.php?page=myriad-voucher-code">No</a></p>
	<?php elseif($page_state == "view_voucher"): ?>
		<table border="1" width="40%">
			<thead>
				<th>Voucher Group</th>
				<th>Voucher Code</th>
				<th>Voucher Order ID</th>
				<th>Number of uses</th>
			</thead>
			<tbody>
				<?php foreach($vouchers as $voucher): ?>
					<tr>
						<td><?php print $voucher->group_id; ?></td>
						<td><?php print $voucher->code; ?></td>
						<td><?php print (int)$voucher->order_id == 0 ? "<font color=\"green\">Available</font>" : $voucher->order_id; ?></td>
						<td><?php print (int)$voucher->number_of_uses==0?"No":"Yes"; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<h3>Upload CSV</h3>
		<form method="post" action="/wp-admin/options-general.php?page=myriad-voucher-code&state=view_voucher&voucher_id=<?php print $_GET['voucher_id']; ?>">
			<p style="color: red;"><?php print @$csv_error_message; ?></p>
			<p style="color: green;"><?php if(isset($_GET['csv'])): ?>CSV has been imported succesfully; <?php endif; ?></p>
			<textarea name="csvinput" rows="10" style="width: 50%;"></textarea><br />
			<input type="radio" name="action" value="append" checked>Append
			<input type="radio" name="action" value="overwrite">Overwrite
			<input type="radio" name="action" value="remove">Remove
			<input type="radio" name="action" value="replace">Replace<br />
			<button type="submit">Upload CSV</button>
		</form>
	<?php endif; ?>
</div>