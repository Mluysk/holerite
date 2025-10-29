<?php
/** @var string $title */
/** @var Holerite\Models\Company $company */
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;"><?= htmlspecialchars($title); ?></h2>
        <p class="muted">Preencha as informações que serão exibidas no recibo de pagamento.</p>
    </header>

    <form method="post" action="?action=update_company">
        <div class="grid">
            <div>
                <label for="name">Razão social</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($company->getName()); ?>" required>
            </div>
            <div>
                <label for="document">CNPJ</label>
                <input type="text" name="document" id="document" value="<?= htmlspecialchars($company->getDocument()); ?>" required>
            </div>
        </div>

        <div class="grid">
            <div>
                <label for="address">Endereço</label>
                <input type="text" name="address" id="address" value="<?= htmlspecialchars($company->getAddress()); ?>" required>
            </div>
            <div>
                <label for="zip_code">CEP</label>
                <input type="text" name="zip_code" id="zip_code" value="<?= htmlspecialchars($company->getZipCode()); ?>" required>
            </div>
        </div>

        <div class="grid">
            <div>
                <label for="city">Cidade</label>
                <input type="text" name="city" id="city" value="<?= htmlspecialchars($company->getCity()); ?>" required>
            </div>
            <div>
                <label for="state">UF</label>
                <input type="text" name="state" id="state" value="<?= htmlspecialchars($company->getState()); ?>" maxlength="2" required>
            </div>
        </div>

        <div class="grid">
            <div>
                <label for="phone">Telefone</label>
                <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($company->getPhone()); ?>" required>
            </div>
            <div>
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($company->getEmail()); ?>" required>
            </div>
        </div>

        <button type="submit" class="button">Salvar</button>
    </form>
</section>
