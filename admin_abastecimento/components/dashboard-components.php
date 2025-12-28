<?php
/**
 * Componentes reutilizáveis do Dashboard
 */

/**
 * Card de Estatística
 * @param string $title Título do card
 * @param string|int $value Valor a exibir
 * @param string $icon Ícone FontAwesome
 * @param string $footer Texto do rodapé (opcional)
 * @param string $color Classe de cor (primary, success, danger, orange)
 */
function renderStatCard($title, $value, $icon = 'fa-chart-bar', $footer = '', $color = 'primary')
{
    ?>
    <div class="card" style="background: #ffffff; border-left: 6px solid #f59b4c;">
        <div class="card-content">
            <div class="card-icon" style="background: #2f6b8f; color: #ffffff;">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <div class="card-info">
                <p class="card-title" style="color: #6c757d;"><?php echo htmlspecialchars($title); ?></p>
                <p class="card-value" style="color: #2f6b8f;"><?php echo htmlspecialchars($value); ?></p>
            </div>
        </div>
        <?php if ($footer): ?>
            <p class="card-footer" style="color: #6c757d;"><i class="fas fa-check-circle" style="margin-right: 5px; color: #1f5734;"></i><?php echo htmlspecialchars($footer); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Tabela com cabeçalho
 * @param array $headers Cabeçalhos da tabela
 * @param array $rows Linhas da tabela
 * @param string $title Título da tabela
 */
function renderTable($headers, $rows, $title = '')
{
    ?>
    <div style="margin-bottom: 30px;">
        <?php if ($title): ?>
            <h3 style="margin-bottom: 20px; color: #333; font-weight: 600;"><?php echo htmlspecialchars($title); ?></h3>
        <?php endif; ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?php echo count($headers); ?>" class="text-center" style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 32px; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                <p style="color: #999;">Nenhum registro encontrado</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Alert/Mensagem
 * @param string $message Mensagem
 * @param string $type Tipo (success, danger, info, warning)
 */
function renderAlert($message, $type = 'info')
{
    $icon = $type === 'success' ? 'fa-check-circle' :
            ($type === 'danger' ? 'fa-exclamation-circle' :
            ($type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'));
    ?>
    <div class="alert alert-<?php echo $type; ?>" role="alert">
        <i class="fas <?php echo $icon; ?>" style="margin-right: 10px;"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php
}

/**
 * Badge/Tag
 * @param string $text Texto do badge
 * @param string $type Tipo (primary, success, danger, orange)
 */
function renderBadge($text, $type = 'primary')
{
    ?>
    <span class="badge badge-<?php echo $type; ?>"><?php echo htmlspecialchars($text); ?></span>
    <?php
}

/**
 * Modal
 * @param string $id ID do modal
 * @param string $title Título
 * @param string $content Conteúdo
 * @param array $buttons Botões do footer
 */
function renderModal($id, $title, $content, $buttons = [])
{
    ?>
    <div id="<?php echo htmlspecialchars($id); ?>" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo htmlspecialchars($title); ?></h3>
                <button class="close" onclick="document.getElementById('<?php echo htmlspecialchars($id); ?>').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php echo $content; ?>
            </div>
            <?php if (!empty($buttons)): ?>
                <div class="modal-footer">
                    <?php foreach ($buttons as $btn): ?>
                        <button class="btn-<?php echo $btn['type'] ?? 'primary'; ?>" onclick="<?php echo $btn['onclick'] ?? ''; ?>">
                            <?php echo htmlspecialchars($btn['text']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Botão
 * @param string $text Texto
 * @param string $type Tipo (primary, secondary, danger, success)
 * @param string $onclick Ação onclick
 * @param string $icon Ícone (opcional)
 */
function renderButton($text, $type = 'primary', $onclick = '', $icon = '')
{
    ?>
    <button class="btn-<?php echo $type; ?>" onclick="<?php echo $onclick; ?>">
        <?php if ($icon): ?>
            <i class="fas <?php echo $icon; ?>"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($text); ?>
    </button>
    <?php
}
