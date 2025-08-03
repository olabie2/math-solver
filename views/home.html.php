<?php startBlock('title') ?>MathLab Home<?php endBlock() ?>

<?php startBlock('content') ?>
<div class="container mx-auto mt-10 text-center">
    <h1 class="text-4xl font-bold text-gray-800">Welcome to MathLab</h1>
    <p class="mt-4 text-lg text-gray-600">This is the homepage for math students.</p>
    <div class="mt-8">
        <a href="/calculator" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
            Go to Calculator
        </a>
    </div>
</div>
<?php endBlock() ?>

<?php renderLayout('layouts/main') ?>
