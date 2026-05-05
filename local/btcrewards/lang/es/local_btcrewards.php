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
$string['setting_points_course_completed_desc'] = 'Puntos por defecto al completar un curso. Se puede sobreescribir por curso en Curso → Más → Recompensas Bitcoin.';
$string['setting_points_quiz_passed'] = 'Puntos por aprobar un cuestionario';
$string['setting_points_quiz_passed_desc'] = 'Puntos por defecto al aprobar un elemento calificado (calificación igual o superior a la nota de aprobación). Se puede sobreescribir por curso.';
$string['setting_points_badge_awarded'] = 'Puntos por obtener una insignia';
$string['setting_points_badge_awarded_desc'] = 'Puntos por defecto al obtener una insignia. Se puede sobreescribir por curso.';

$string['task_process_payout_queue'] = 'Procesar cola de pagos de recompensas Bitcoin';

$string['error_source_unavailable'] = 'La fuente de puntos "{$a}" no está disponible.';
$string['error_payment_http'] = 'Error HTTP del servicio de pago: {$a}';

$string['course_config_nav'] = 'Recompensas Bitcoin';
$string['course_config_title'] = 'Recompensas Bitcoin para este curso';
$string['course_config_enabled'] = 'Habilitar recompensas para este curso';
$string['course_config_enabled_help'] = 'Si está desmarcado, no se otorgan puntos por la actividad en este curso. Las recompensas requieren activación por curso.';
$string['course_config_override'] = 'Sobreescritura por curso';
$string['course_config_override_help'] = 'Dejar vacío para usar el valor por defecto del sitio. Ingrese 0 o más para sobreescribir en este curso.';
$string['course_config_must_be_int'] = 'Debe ser un número entero no negativo o dejarse vacío.';

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
$string['claim_onchain_below_min'] = 'Los pagos en Bitcoin onchain requieren al menos USD {$a}. Tu cobro es menor — usá una Lightning invoice o Lightning address.';
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
$string['my_retry_button'] = 'Intentar de nuevo con otro destino';
$string['my_retry_hint'] = 'Libera los puntos a tu saldo para que puedas enviar un nuevo cobro.';
$string['my_requeue_ok'] = 'Puntos liberados — enviá un nuevo cobro abajo.';
$string['my_requeued_note'] = 'Los puntos de este intento volvieron a tu saldo.';
$string['payout_status_requeued'] = 'Puntos devueltos';
$string['requeue_forbidden'] = 'Solo podés reintentar tus propios cobros.';
$string['requeue_not_failed'] = 'Solo los cobros fallidos se pueden reintentar.';
$string['my_col_claimed'] = 'Estado';
$string['my_status_claimed'] = 'Reclamado';
$string['my_status_unclaimed'] = 'Sin reclamar';
$string['my_payout_rewards'] = 'Recompensas incluidas en este pago';
$string['my_empty_queue'] = 'Aún no hay pagos.';
$string['my_claimed_ok'] = 'Pago en cola. Se procesará en la próxima ejecución del cron.';

$string['claim_misconfigured'] = 'Las recompensas no están completamente configuradas. Contacta al administrador del sitio.';
$string['claim_below_threshold'] = 'Aún no tienes suficientes puntos sin reclamar para cobrar.';
$string['claim_no_destination'] = 'Por favor ingresa una dirección Bitcoin.';
