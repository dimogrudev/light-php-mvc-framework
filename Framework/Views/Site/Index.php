<?php

/**
 * @var Framework\Models\Customer[] $customers
 */

?>
<div class="row">
	<div class="col">
		<h1>Lorem Ipsum</h1>
		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
	</div>
</div>
<div class="row">
	<div class="col">
		<table class="table">
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col">Full Name</th>
					<th scope="col">Age</th>
					<th scope="col">Place of Birth</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($customers as $customer) : ?>
					<tr>
						<th scope="row"><?= $customer->getId(); ?></th>
						<td><?= $customer->fullName; ?></td>
						<td><?= $customer->age; ?></td>
						<td><?= $customer->placeOfBirth; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>