<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Cadenas en español para local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Recompensas Bitcoin';

$string['source_native'] = 'Nativo (integrado)';
$string['source_xp'] = 'Level Up XP';

$string['setting_points_source'] = 'Fuente de puntos';
$string['setting_points_source_desc'] = 'Backend utilizado para leer y escribir los puntos de los estudiantes.';
$string['setting_min_payout_usd'] = 'Cobro mínimo (USD)';
$string['setting_min_payout_usd_desc'] = 'Monto mínimo en USD que un estudiante debe acumular antes de poder cobrar. En decimales: 0.50 = USD 0,50, 1 = USD 1,00, 5 = USD 5,00. La red Lightning acepta cualquier monto por encima de este umbral. Los pagos en Bitcoin onchain tienen además un mínimo en vivo definido por el servicio de swap (hoy alrededor de USD 19-20); cuando el cobro está por debajo de ese piso, la opción onchain aparece como no disponible.';
$string['setting_usd_per_point'] = 'USD por punto';
$string['setting_usd_per_point_desc'] = 'Valor en USD por punto (decimales). Ejemplos: 0.01 = USD 0,01 por punto, 0.10 = USD 0,10 por punto, 1 = USD 1,00 por punto. El monto en BTC se calcula al momento del cobro con el precio BTC/USD en vivo.';
$string['setting_max_attempts'] = 'Intentos máximos de pago';
$string['setting_max_attempts_desc'] = 'Número máximo de veces que el procesador de la cola reintentará un pago fallido.';
$string['setting_payment_service_url'] = 'URL del servicio de pago';
$string['setting_payment_service_url_desc'] = 'URL base del microservicio interno de pagos Lightning.';
$string['setting_payment_service_secret'] = 'Secreto del servicio de pago';
$string['setting_payment_service_secret_desc'] = 'Secreto compartido enviado al servicio de pago mediante el encabezado X-Internal-Token.';
$string['setting_points_course_completed'] = 'Puntos por completar un curso';
$string['setting_points_course_completed_desc'] = 'Puntos otorgados al completar un curso. Se configura por curso en Curso → Más → Recompensas Bitcoin.';

$string['task_process_payout_queue'] = 'Procesar cola de pagos de recompensas Bitcoin';

$string['error_source_unavailable'] = 'La fuente de puntos "{$a}" no está disponible.';
$string['error_payment_http'] = 'Error HTTP del servicio de pago: {$a}';

$string['course_config_nav'] = 'Recompensas Bitcoin';
$string['course_config_title'] = 'Recompensas Bitcoin para este curso';
$string['course_config_enabled'] = 'Habilitar recompensas para este curso';
$string['course_config_enabled_help'] = 'Si está desmarcado, no se otorgan puntos por la actividad en este curso. Las recompensas requieren activación por curso.';
$string['course_config_override'] = 'Puntos otorgados';
$string['course_config_override_help'] = 'Cuántos puntos otorga este curso para el evento. Dejá vacío (o 0) para no dar puntos por este evento en este curso.';
$string['course_config_must_be_int'] = 'Debe ser un número entero no negativo o dejarse vacío.';
$string['course_config_quizzes_heading'] = 'Recompensas por cuestionario';
$string['course_config_quizzes_help'] = 'Puntos a otorgar cuando un usuario aprueba cada elemento calificado. Dejá vacío (o 0) para no otorgar puntos.';
$string['course_config_quizzes_empty'] = 'Este curso aún no tiene elementos calificables.';
$string['course_config_badges_heading'] = 'Recompensas por insignia';
$string['course_config_badges_help'] = 'Puntos a otorgar cuando un usuario obtiene cada insignia del curso. Dejá vacío (o 0) para no otorgar puntos. Las insignias del sitio no son elegibles.';
$string['course_config_badges_empty'] = 'Este curso aún no tiene insignias.';
$string['course_config_claim_mode'] = 'Modo de reclamo';
$string['course_config_claim_mode_help'] = 'Cómo se dispara el pago de los puntos ganados en este curso. La aprobación de un administrador es el modo más seguro: el estudiante envía la solicitud, pero un administrador debe aprobarla antes de que se realice el pago.';
$string['course_config_claim_mode_self'] = 'Autoservicio del estudiante (el reclamo se envía al servicio de pago de inmediato)';
$string['course_config_claim_mode_admin_approval'] = 'Requiere aprobación de admin (el reclamo queda en espera hasta que un admin lo apruebe)';

$string['admin_claim_title'] = 'Recompensas Bitcoin: pagos';
$string['admin_claim_pending_heading'] = 'Esperando aprobación de admin';
$string['admin_claim_pending_empty'] = 'No hay reclamos pendientes.';
$string['admin_claim_initiate_heading'] = 'Iniciar pago para un usuario';
$string['admin_claim_initiate_empty'] = 'Ningún usuario tiene puntos sin reclamar por encima del umbral.';
$string['admin_claim_col_user'] = 'Usuario';
$string['admin_claim_col_amount'] = 'Monto';
$string['admin_claim_col_unclaimed'] = 'Sin reclamar';
$string['admin_claim_col_destination'] = 'Destino';
$string['admin_claim_col_when'] = 'Enviado';
$string['admin_claim_col_actions'] = 'Acciones';
$string['admin_claim_approve'] = 'Aprobar';
$string['admin_claim_reject'] = 'Rechazar';
$string['admin_claim_pay'] = 'Pagar';
$string['admin_claim_approved'] = 'Reclamo aprobado — el pago se enviará en el próximo cron.';
$string['admin_claim_rejected'] = 'Reclamo rechazado — los puntos volvieron al usuario.';
$string['admin_claim_submitted'] = 'Pago en cola.';
$string['admin_failed_heading'] = 'Pagos fallidos';
$string['admin_failed_empty'] = 'No hay pagos fallidos.';
$string['admin_failed_col_error'] = 'Error';
$string['admin_failed_release'] = 'Liberar puntos';
$string['admin_failed_released'] = 'Puntos liberados — el usuario puede reclamar de nuevo.';
$string['admin_recent_heading'] = 'Todos los pagos';
$string['admin_recent_empty'] = 'No hay pagos aún.';
$string['admin_recent_col_attempts'] = 'Intentos';
$string['approval_not_pending'] = 'Este reclamo no está en estado de aprobación pendiente.';
$string['payout_status_pending_approval'] = 'Esperando aprobación de admin';

$string['my_title'] = 'Mis recompensas Bitcoin';
$string['my_nav'] = 'Mis recompensas Bitcoin';
$string['my_total_points'] = 'Puntos totales obtenidos';
$string['my_unclaimed_points'] = 'Puntos sin reclamar';
$string['my_threshold'] = 'Umbral de cobro';
$string['my_usd_per_point'] = 'USD por punto';
$string['my_current_rate'] = 'BTC/USD';
$string['my_claim_value'] = 'Podés cobrar';
$string['my_claim_value_sats'] = '{$a} sats';
$string['my_rate_unavailable'] = 'No se pudo obtener el precio BTC/USD — los cobros están en pausa. Detalle: {$a}';
$string['error_rate_unavailable'] = 'Precio BTC/USD no disponible: {$a}';
$string['my_below_threshold'] = 'Sigue aprendiendo — necesitás al menos USD {$a} en recompensas sin reclamar para cobrar.';
$string['my_onchain_available'] = 'Bitcoin onchain (bc1…) está disponible para este cobro — mínimo {$a}.';
$string['my_onchain_unavailable'] = 'Bitcoin onchain no disponible — se requiere al menos {$a}. Usá un destino Lightning.';
$string['error_limits_unavailable'] = 'Límites del servicio de pagos no disponibles: {$a}';
$string['error_parse_unavailable'] = 'No se pudo validar el destino: {$a}';
$string['claim_onchain_below_min'] = 'Los pagos en Bitcoin onchain requieren al menos USD {$a}. Tu cobro es menor — usá una Lightning invoice o Lightning address.';
$string['claim_bolt11_amount_mismatch'] = 'La Lightning invoice que pegaste espera {$a->invoice} sats pero tu cobro es por {$a->expected} sats. Genera una nueva invoice por exactamente {$a->expected} sats, o pega una Lightning address (usuario@dominio) que no tiene monto fijo.';
$string['my_payout_amount_label'] = 'Vas a recibir';
$string['my_address_label'] = '¿A dónde lo enviamos?';
$string['my_address_placeholder'] = 'bc1…, lnbc…, lno… o usuario@dominio';
$string['my_address_help'] = 'Pegá una de estas opciones:<ul class="mb-0 mt-1 ps-3"><li>Dirección Bitcoin onchain (<code>bc1…</code>)</li><li>Lightning invoice por <strong>exactamente {$a} sats</strong> (<code>lnbc…</code>)</li><li>Lightning offer (<code>lno…</code>)</li><li>Lightning address (<code>usuario@dominio</code>)</li></ul>';
$string['my_address_warning'] = '⚠ Los pagos son definitivos y no se pueden revertir. Verificá antes de enviar.';
$string['my_claim_button'] = 'Reclamar recompensas';
$string['my_history_heading'] = 'Historial de puntos';
$string['my_queue_heading'] = 'Pagos';
$string['my_col_when'] = 'Cuándo';
$string['my_col_event'] = 'Evento';
$string['my_col_points'] = 'Puntos';
$string['my_col_sats'] = 'Sats';
$string['my_col_status'] = 'Estado';
$string['my_col_destination'] = 'Destino';
$string['my_col_dest_type'] = 'Tipo';
$string['my_col_txid'] = 'Transacción';

$string['dest_type_onchain'] = 'Onchain';
$string['dest_type_ln_address'] = 'Dirección Lightning';
$string['dest_type_bolt11'] = 'Lightning invoice (BOLT11)';
$string['dest_type_bolt12'] = 'Oferta Bolt12';
$string['dest_type_auto'] = 'Detectando…';

$string['payout_status_pending'] = 'Pendiente';
$string['payout_status_accepted'] = 'Aceptado';
$string['payout_status_processing'] = 'Procesando';
$string['payout_status_paid'] = 'Pagado';
$string['payout_status_failed'] = 'Fallido';
$string['my_progress_label'] = '{$a->current} / {$a->threshold} hacia el próximo cobro';
$string['my_courses_heading'] = 'Puntos por curso';
$string['my_course_unknown'] = 'Otro';
$string['my_empty_history'] = 'Aún no has obtenido puntos.';

$string['component_course'] = 'Curso completado';
$string['component_grade_items'] = 'Cuestionario aprobado';
$string['component_badge'] = 'Insignia obtenida';
$string['component_legacy'] = 'Legado';
$string['component_manual'] = 'Asignación manual';
$string['my_requeued_note'] = 'Los puntos de este intento volvieron a tu saldo.';
$string['my_failed_admin_contact'] = 'Este pago falló. Contacta al administrador del sitio para liberar los puntos y poder reclamarlos de nuevo.';
$string['payout_status_requeued'] = 'Puntos devueltos';
$string['requeue_not_failed'] = 'Solo los cobros fallidos se pueden liberar.';
$string['claim_concurrent'] = 'Ya hay otro cobro en curso para estos puntos. Recargá la página y probá de nuevo.';
$string['my_col_claimed'] = 'Estado';
$string['my_status_claimed'] = 'Reclamado';
$string['my_status_unclaimed'] = 'Sin reclamar';
$string['my_payout_rewards'] = 'Recompensas incluidas en este pago';
$string['my_empty_queue'] = 'Aún no hay pagos.';
$string['my_claimed_ok'] = 'Pago en cola. Se procesará en la próxima ejecución del cron.';

$string['claim_misconfigured'] = 'Las recompensas no están completamente configuradas. Contacta al administrador del sitio.';
$string['claim_below_threshold'] = 'Aún no tienes suficientes puntos sin reclamar para cobrar.';
$string['claim_no_destination'] = 'Por favor ingresa una dirección Bitcoin.';
