<?php
/**
 * @var array{text:string, checked:bool, priority:string, due:string} $item
 * @var int $index
 */
$accent = match ($item['priority'] ?? 'medium') {
    'high'   => 'danger',
    'low'    => 'success',
    default  => 'primary',
};
$due = format_relative($item['due']);
?>
<div class="d-flex align-items-center mb-8" data-todo-index="<?= (int) $index ?>">
	<span class="bullet bullet-vertical h-40px bg-<?= e($accent) ?>"></span>
	<div class="form-check form-check-custom form-check-solid mx-5">
		<input class="form-check-input js-todo-toggle" type="checkbox" value="1"
			<?= !empty($item['checked']) ? 'checked' : '' ?> />
	</div>
	<div class="flex-grow-1">
		<a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6 <?= !empty($item['checked']) ? 'text-decoration-line-through' : '' ?>">
			<?= e($item['text']) ?>
		</a>
		<span class="text-muted fw-semibold d-block">Due <?= e($due) ?></span>
	</div>
	<span class="badge badge-light-<?= e($accent) ?> fs-8 fw-bold"><?= e(ucfirst($item['priority'] ?? 'medium')) ?></span>
</div>
