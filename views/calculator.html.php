<?php startBlock('title') ?>Calculator<?php endBlock() ?>

<?php startBlock('content') ?>
<div class="container mx-auto mt-10">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Interactive Math Notation Input</h1>

        <form action="/calculator" method="POST" id="math-form">
            <math-field id="mathfield" class="w-full text-2xl p-4 border border-gray-300 rounded-lg shadow-inner focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?= $submitted_latex ?? 'y = mx + b' ?></math-field>
            <input type="hidden" name="latex" id="latex-input" value="<?= $submitted_latex ?? 'y = mx + b' ?>">

            <div class="grid grid-cols-5 gap-2 my-4">
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\frac{#0}{#1}' title="Fraction (x/y)"><sup>x</sup>⁄<sub>y</sub></button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='{#0}^{#1}' title="Power (x^y)">x<sup>y</sup></button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\sqrt{#0}' title="Square Root">√</button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\sqrt[#1]{#0}' title="Nth Root"><sup>n</sup>√</button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='(#0)' title="Parentheses">( )</button>

                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-xl" data-command='+' title="Add">+</button>
                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-xl" data-command='-' title="Subtract">-</button>
                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-xl" data-command='\times' title="Multiply">×</button>
                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-xl" data-command='\div' title="Divide">÷</button>
                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-xl" data-command='=' title="Equals">=</button>

                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\pi' title="Pi">π</button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\sin' title="Sine">sin</button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\cos' title="Cosine">cos</button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\tan' title="Tangent">tan</button>
                <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded" data-command='\log' title="Log">log</button>
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Submit Expression
                </button>
            </div>
        </form>

        <div class="mt-6 p-4 bg-gray-800 text-white rounded-lg">
            <h3 class="font-semibold">Live LaTeX Output:</h3>
            <pre id="latex-output" class="text-sm whitespace-pre-wrap break-words"><?= $submitted_latex ?? 'y = mx + b' ?></pre>
        </div>

        <?php if (isset($solution)): ?>
            <div class="mt-6 p-4 bg-gray-100 rounded-lg shadow">
                <h3 class="text-xl font-semibold text-gray-700 mb-3">Solution:</h3>
                <p class="text-gray-600 mb-2"><strong>Submitted Expression:</strong> <?= htmlspecialchars($solution['expression']) ?></p>
                <?= $solutionHtml ?? '' ?>

                <?php if (!empty($debug_info)): ?>
                    <div class="mt-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                        <p class="font-bold">Debugging Information (Presenter Failed):</p>
                        <pre class="mt-2 text-sm whitespace-pre-wrap"><?= htmlspecialchars($debug_info) ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://unpkg.com/mathlive"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mathfield = document.getElementById('mathfield');
        const latexOutput = document.getElementById('latex-output');
        const latexInput = document.getElementById('latex-input');

        // Initialize mathfield and latex output with submitted_latex if available
        if (latexInput.value) {
            mathfield.value = latexInput.value;
            latexOutput.textContent = latexInput.value;
        }

        mathfield.addEventListener('input', () => {
            const latex = mathfield.value;
            latexOutput.textContent = latex;
            latexInput.value = latex;
        });

        document.querySelectorAll('button[data-command]').forEach(button => {
            button.addEventListener('click', () => {
                const command = button.dataset.command;
                if (command) {
                    mathfield.executeCommand(['insert', command]);
                    mathfield.focus();
                }
            });
        });
    });
</script>
<?php endBlock() ?>

<?php renderLayout('layouts/main') ?>