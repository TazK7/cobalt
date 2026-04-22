<?php /** @var array{name:string, role:string, avatar:string} $person */ ?>
<div class="d-flex align-items-center mb-7">
	<div class="symbol symbol-50px me-5">
		<img src="<?= e($person['avatar']) ?>" alt="<?= e($person['name']) ?>" />
	</div>
	<div class="flex-grow-1">
		<a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6"><?= e($person['name']) ?></a>
		<span class="text-muted d-block fw-bold"><?= e($person['role']) ?></span>
	</div>
</div>
