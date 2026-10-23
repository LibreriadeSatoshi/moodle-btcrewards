<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Brazilian Portuguese strings for local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Recompensas Bitcoin';

$string['source_native'] = 'Nativo (integrado)';
$string['source_xp'] = 'Level Up XP';

$string['setting_points_source'] = 'Fonte de pontos';
$string['setting_points_source_desc'] = 'Backend usado para ler e gravar os pontos dos estudantes.';
$string['setting_min_payout_usd'] = 'Saque mínimo (USD)';
$string['setting_min_payout_usd_desc'] = 'Valor mínimo em USD que um estudante precisa acumular antes de poder sacar. Em decimais: 0.50 = USD 0,50, 1 = USD 1,00, 5 = USD 5,00. A rede Lightning aceita qualquer valor acima deste limite.';
$string['setting_usd_per_point'] = 'USD por ponto';
$string['setting_usd_per_point_desc'] = 'Valor em USD por ponto (decimais). Exemplos: 0.01 = USD 0,01 por ponto, 0.10 = USD 0,10 por ponto, 1 = USD 1,00 por ponto. O valor em BTC é calculado no momento do saque com o preço BTC/USD ao vivo.';
$string['setting_max_attempts'] = 'Tentativas máximas de pagamento';
$string['setting_max_attempts_desc'] = 'Número máximo de vezes que o processador da fila tentará novamente um pagamento que falhou.';
$string['setting_allowed_ln_domain'] = 'Domínio permitido para endereços Lightning';
$string['setting_allowed_ln_domain_desc'] = 'Só endereços Lightning deste domínio podem receber pagamentos. Qualquer outro domínio é recusado no momento do resgate.';
$string['setting_payment_service_url'] = 'URL do serviço de pagamento';
$string['setting_payment_service_url_desc'] = 'URL base do microsserviço interno de pagamentos Lightning.';
$string['setting_payment_service_secret'] = 'Segredo do serviço de pagamento';
$string['setting_payment_service_secret_desc'] = 'Segredo compartilhado enviado ao serviço de pagamento no cabeçalho X-Internal-Token.';
$string['setting_webhook_secret'] = 'Segredo do webhook';
$string['setting_webhook_secret_desc'] = 'Segredo compartilhado usado para verificar o cabeçalho X-Webhook-Signature (HMAC-SHA256) das notificações de pagamento recebidas. Deve ser igual ao WEBHOOK_SECRET do serviço de pagamento.';
$string['setting_webhook_url'] = 'URL do webhook';
$string['setting_webhook_url_desc'] = 'Configure o MOODLE_WEBHOOK_URL do serviço de pagamento para enviar (POST) os eventos finais de pagamento para: <code>{$a}</code>';
$string['setting_points_course_completed'] = 'Pontos por concluir um curso';
$string['setting_points_course_completed_desc'] = 'Pontos concedidos ao concluir um curso. Configure em cada curso em Curso → Mais → Recompensas Bitcoin.';

$string['task_process_payout_queue'] = 'Processar a fila de pagamentos das recompensas Bitcoin';

$string['error_source_unavailable'] = 'A fonte de pontos "{$a}" não está disponível.';
$string['error_payment_http'] = 'Erro HTTP do serviço de pagamento: {$a}';

$string['course_config_nav'] = 'Recompensas Bitcoin';
$string['course_config_title'] = 'Recompensas Bitcoin para este curso';
$string['course_config_enabled'] = 'Ativar recompensas para este curso';
$string['course_config_enabled_help'] = 'Se desmarcado, nenhum ponto é concedido pela atividade neste curso. As recompensas precisam ser ativadas em cada curso.';
$string['course_config_override'] = 'Pontos concedidos';
$string['course_config_override_help'] = 'Quantos pontos este curso concede pelo evento. Deixe vazio (ou 0) para não dar pontos por este evento neste curso.';
$string['course_config_must_be_int'] = 'Deve ser um número inteiro não negativo ou ficar vazio.';
$string['course_config_quizzes_heading'] = 'Recompensas por questionário';
$string['course_config_quizzes_help'] = 'Pontos a conceder quando um usuário é aprovado em cada item avaliado. Deixe vazio (ou 0) para não conceder pontos.';
$string['course_config_quizzes_empty'] = 'Este curso ainda não tem itens avaliáveis.';
$string['course_config_badges_heading'] = 'Recompensas por emblema';
$string['course_config_badges_help'] = 'Pontos a conceder quando um usuário obtém cada emblema do curso. Deixe vazio (ou 0) para não conceder pontos. Emblemas do site não são elegíveis.';
$string['course_config_badges_empty'] = 'Este curso ainda não tem emblemas.';
$string['course_config_claim_mode'] = 'Modo de resgate';
$string['course_config_claim_mode_help'] = 'Como é disparado o pagamento dos pontos ganhos neste curso. A aprovação de um administrador é o modo mais seguro: o estudante envia a solicitação, mas um administrador precisa aprová-la antes do pagamento.';
$string['course_config_claim_mode_self'] = 'Autoatendimento do estudante (o resgate vai ao serviço de pagamento imediatamente)';
$string['course_config_claim_mode_admin_approval'] = 'Requer aprovação do admin (o resgate fica em espera até um admin aprovar)';

$string['admin_claim_title'] = 'Recompensas Bitcoin: pagamentos';
$string['admin_balance_heading'] = 'Saldo da carteira';
$string['admin_balance_pending_send'] = 'Pendente de saída';
$string['admin_balance_pending_receive'] = 'Pendente de entrada';
$string['admin_balance_unavailable'] = 'Não foi possível obter o saldo da carteira: {$a}';
$string['admin_fund_amount_placeholder'] = 'Valor (sats)';
$string['admin_fund_generate'] = 'Gerar fatura de depósito';
$string['admin_fund_invoice_help'] = 'Pague esta fatura Lightning a partir de uma carteira externa para abastecer a carteira de pagamentos. Ela expira em 1 hora.';
$string['error_balance_unavailable'] = 'Saldo do serviço de pagamentos indisponível: {$a}';
$string['error_deposit_unavailable'] = 'Não foi possível criar a fatura de depósito: {$a}';
$string['admin_claim_pending_heading'] = 'Aguardando aprovação do admin';
$string['admin_claim_pending_empty'] = 'Não há resgates pendentes.';
$string['admin_claim_initiate_heading'] = 'Iniciar pagamento para um usuário';
$string['admin_claim_initiate_empty'] = 'Nenhum usuário tem pontos não resgatados acima do limite.';
$string['admin_claim_col_user'] = 'Usuário';
$string['admin_claim_col_amount'] = 'Valor';
$string['admin_claim_col_unclaimed'] = 'Não resgatado';
$string['admin_claim_col_destination'] = 'Destino';
$string['admin_claim_col_when'] = 'Enviado';
$string['admin_claim_col_actions'] = 'Ações';
$string['admin_claim_approve'] = 'Aprovar';
$string['admin_claim_reject'] = 'Recusar';
$string['admin_claim_pay'] = 'Pagar';
$string['admin_claim_approved'] = 'Resgate aprovado. O pagamento será enviado no próximo cron.';
$string['admin_claim_rejected'] = 'Resgate recusado. Os pontos voltaram para o usuário.';
$string['admin_claim_submitted'] = 'Pagamento na fila.';
$string['admin_failed_heading'] = 'Pagamentos que falharam';
$string['admin_failed_empty'] = 'Não há pagamentos que falharam.';
$string['admin_failed_col_error'] = 'Erro';
$string['admin_failed_release'] = 'Liberar pontos';
$string['admin_failed_released'] = 'Pontos liberados. O usuário pode resgatar de novo.';
$string['admin_recent_col_attempts'] = 'Tentativas';
$string['admin_browse_heading'] = 'Todos os pagamentos';
$string['admin_browse_status'] = 'Status';
$string['admin_browse_status_any'] = 'Qualquer status';
$string['admin_browse_user'] = 'Usuário';
$string['admin_browse_user_placeholder'] = 'Nome ou e-mail';
$string['admin_browse_dest'] = 'Destino ou txid';
$string['admin_browse_dest_placeholder'] = 'lnbc…, bc1… ou txid completo';
$string['admin_browse_from'] = 'De';
$string['admin_browse_to'] = 'Até';
$string['admin_browse_apply'] = 'Aplicar filtros';
$string['admin_browse_clear'] = 'Limpar';
$string['admin_browse_count'] = '{$a->count} pagamentos encontrados';
$string['admin_browse_empty'] = 'Nenhum pagamento corresponde a estes filtros.';
$string['approval_not_pending'] = 'Este resgate não está aguardando aprovação.';
$string['payout_status_pending_approval'] = 'Aguardando aprovação do admin';

$string['my_title'] = 'Minhas recompensas Bitcoin';
$string['my_nav'] = 'Minhas recompensas Bitcoin';
$string['my_total_points'] = 'Total de pontos ganhos';
$string['my_unclaimed_points'] = 'Pontos não resgatados';
$string['my_threshold'] = 'Mínimo para saque';
$string['my_usd_per_point'] = 'USD por ponto';
$string['my_current_rate'] = 'BTC/USD';
$string['my_claim_value'] = 'Você pode sacar';
$string['my_claim_value_sats'] = '{$a} sats';
$string['my_rate_unavailable'] = 'Não foi possível obter o preço BTC/USD. Os saques estão pausados. Detalhe: {$a}';
$string['error_rate_unavailable'] = 'Preço BTC/USD indisponível: {$a}';
$string['my_below_threshold'] = 'Continue aprendendo: você precisa de pelo menos USD {$a} em recompensas não resgatadas para sacar.';
$string['claim_invalid_ln_address'] = 'Só são aceitos endereços Lightning do domínio <strong>{$a}</strong> (por exemplo, <code>voce@{$a}</code>).';
$string['my_payout_amount_label'] = 'Você vai receber';
$string['my_address_label'] = 'Para onde enviamos?';
$string['my_address_placeholder'] = 'voce@{$a}';
$string['my_address_help'] = 'Digite seu Lightning address no domínio <code>{$a}</code> (por exemplo, <code>voce@{$a}</code>).';
$string['my_address_get_one'] = 'Ainda não tem um? Crie seu Lightning address em <a href="https://wallet.ismos.xyz/" target="_blank" rel="noopener">wallet.ismos.xyz</a>.';
$string['my_save_address'] = 'Salvar para a próxima vez';
$string['profile_field_lnaddress'] = 'Endereço Lightning';
$string['profile_field_lnaddress_desc'] = 'Seu endereço Lightning para receber os pagamentos das recompensas em Bitcoin.';
$string['my_ofac_label'] = 'Não estou em uma jurisdição sancionada pela OFAC';
$string['my_ofac_list'] = 'Atualmente: Cuba, Irã, Coreia do Norte, Síria e as regiões da Crimeia, Donetsk e Luhansk, na Ucrânia.';
$string['my_ofac_required'] = 'Você precisa confirmar que não está em uma jurisdição sancionada pela OFAC antes de resgatar.';
$string['my_claim_button'] = 'Resgatar recompensas';
$string['my_history_heading'] = 'Histórico de pontos';
$string['my_queue_heading'] = 'Pagamentos';
$string['my_col_when'] = 'Quando';
$string['my_col_event'] = 'Evento';
$string['my_col_points'] = 'Pontos';
$string['my_col_sats'] = 'Sats';
$string['my_col_status'] = 'Status';
$string['my_col_destination'] = 'Destino';
$string['my_col_dest_type'] = 'Tipo';
$string['my_col_txid'] = 'Transação';

$string['dest_type_ln_address'] = 'Endereço Lightning';

$string['payout_status_pending'] = 'Pendente';
$string['payout_status_accepted'] = 'Aceito';
$string['payout_status_processing'] = 'Processando';
$string['payout_status_paid'] = 'Pago';
$string['payout_status_failed'] = 'Falhou';
$string['my_progress_label'] = '{$a->current} / {$a->threshold} para o próximo saque';
$string['my_courses_heading'] = 'Pontos por curso';
$string['my_course_unknown'] = 'Outro';
$string['my_empty_history'] = 'Você ainda não ganhou pontos.';

$string['component_course'] = 'Curso concluído';
$string['component_grade_items'] = 'Questionário aprovado';
$string['component_badge'] = 'Emblema obtido';
$string['component_legacy'] = 'Legado';
$string['component_manual'] = 'Atribuição manual';
$string['my_requeued_note'] = 'Os pontos desta tentativa voltaram para o seu saldo.';
$string['my_failed_admin_contact'] = 'Este pagamento falhou. Fale com o administrador do site para liberar os pontos e poder resgatá-los de novo.';
$string['payout_status_requeued'] = 'Pontos devolvidos';
$string['requeue_not_failed'] = 'Só saques que falharam podem ser liberados.';
$string['claim_concurrent'] = 'Já existe outro saque em andamento para estes pontos. Recarregue a página e tente de novo.';
$string['my_col_claimed'] = 'Status';
$string['my_status_claimed'] = 'Resgatado';
$string['my_status_unclaimed'] = 'Não resgatado';
$string['my_payout_rewards'] = 'Recompensas incluídas neste pagamento';
$string['my_empty_queue'] = 'Ainda não há pagamentos.';
$string['my_claimed_ok'] = 'Pagamento na fila. Ele será processado na próxima execução do cron.';

$string['claim_misconfigured'] = 'As recompensas não estão totalmente configuradas. Fale com o administrador do site.';
$string['claim_below_threshold'] = 'Você ainda não tem pontos não resgatados suficientes para sacar.';
$string['claim_no_destination'] = 'Digite um endereço Bitcoin.';

$string['privacy:metadata:points_table'] = 'As recompensas obtidas por um usuário.';
$string['privacy:metadata:payouts_table'] = 'Os saques solicitados pelo usuário e seu histórico de pagamentos.';
$string['privacy:metadata:items_table'] = 'A relação entre cada saque e as recompensas que ele consome.';
$string['privacy:metadata:userid'] = 'O usuário que obteve a recompensa ou solicitou o saque.';
$string['privacy:metadata:courseid'] = 'O curso em que a recompensa foi obtida.';
$string['privacy:metadata:points'] = 'A quantidade de pontos de recompensa obtidos.';
$string['privacy:metadata:component'] = 'O tipo de atividade que concedeu a recompensa.';
$string['privacy:metadata:itemid'] = 'A atividade ou o item que concedeu a recompensa.';
$string['privacy:metadata:timecreated'] = 'Quando o registro foi criado.';
$string['privacy:metadata:timemodified'] = 'Quando o saque foi atualizado pela última vez.';
$string['privacy:metadata:usd_cents'] = 'O valor do saque em centavos de dólar.';
$string['privacy:metadata:btc_usd_rate'] = 'A taxa de câmbio aplicada ao saque.';
$string['privacy:metadata:sats'] = 'O valor do pagamento em satoshis.';
$string['privacy:metadata:destination'] = 'O endereço, fatura ou oferta de pagamento do usuário.';
$string['privacy:metadata:dest_type'] = 'O tipo de destino do pagamento.';
$string['privacy:metadata:status'] = 'O status de processamento do saque.';
$string['privacy:metadata:txid'] = 'A referência da transação do pagamento.';
$string['privacy:metadata:preimage'] = 'A prova de liquidação do pagamento Lightning.';
$string['privacy:metadata:attempts'] = 'A quantidade de tentativas de pagamento.';
$string['privacy:metadata:last_error'] = 'O último erro de pagamento.';
$string['privacy:metadata:payoutid'] = 'O saque associado às recompensas obtidas.';
$string['privacy:metadata:pointsid'] = 'A recompensa obtida incluída em um saque.';
$string['privacy:metadata:payment_service'] = 'Os valores e destinos de pagamento são enviados ao serviço de pagamento configurado, que mantém um registro de cada saque.';
$string['privacy:metadata:profilefield'] = 'O endereço Lightning salvo é armazenado em um campo de perfil personalizado e é exportado e excluído junto com os demais campos de perfil do usuário.';
