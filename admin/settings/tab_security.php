<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-warning h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key mr-1"></i> APIs & Clés</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><i class="fas fa-map-marked-alt text-warning mr-1"></i> Google Maps (Iframe)</label>
                    <textarea name="google_maps_code" class="form-control" rows="6" style="background:#2d3436; color:#dfe6e9; font-family: monospace; font-size: 12px;" placeholder='<iframe src="...">'><?php echo htmlspecialchars(base64_decode($settings['google_maps_code'])); ?></textarea>
                    <small class="text-muted">Collez ici le code d'intégration (Embed) fourni par Google Maps.</small>
                </div>
                
                <hr>
                <label><i class="fas fa-shield-alt text-success mr-1"></i> Google reCAPTCHA v2</label>
                <div class="form-group mb-2">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="width: 90px;">Site Key</span>
                        </div>
                        <input type="text" name="gcaptcha_sitekey" class="form-control" value="<?php echo htmlspecialchars($settings['gcaptcha_sitekey']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group input-group-sm">
                         <div class="input-group-prepend">
                            <span class="input-group-text" style="width: 90px;">Secret Key</span>
                        </div>
                        <input type="text" name="gcaptcha_secretkey" class="form-control" value="<?php echo htmlspecialchars($settings['gcaptcha_secretkey']); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-outline card-danger h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-code mr-1"></i> Injection de Code (Avancé)</h3>
            </div>
            <div class="card-body">
                <div class="callout callout-danger py-2 px-3 mb-3">
                    <small><strong>Attention :</strong> Un code malformé ici peut casser l'affichage de votre site. Utilisez avec précaution (ex: Google Analytics).</small>
                </div>
                
                <div class="form-group">
                    <label>Code personnalisé (Head)</label>
                    <textarea name="head_customcode" class="form-control font-monospace" rows="10" style="background:#2d3436; color:#dfe6e9; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars(base64_decode($settings['head_customcode'])); ?></textarea>
                    <small class="text-muted">Ce code sera injecté juste avant la balise <code>&lt;/head&gt;</code>.</small>
                </div>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="customCodeSwitch" disabled <?php if ($settings['head_customcode_enabled'] == 'On') echo 'checked'; ?>>
                      <label class="custom-control-label" for="customCodeSwitch">Statut (Contrôlé via Select ci-dessous)</label>
                    </div>
                    <div class="mt-1">
                        <select name="head_customcode_enabled" class="form-control custom-select custom-select-sm" style="width: 150px;">
                            <option value="On" <?php if ($settings['head_customcode_enabled'] == 'On') echo 'selected'; ?>>Activé</option>
                            <option value="Off" <?php if ($settings['head_customcode_enabled'] == 'Off') echo 'selected'; ?>>Désactivé</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>