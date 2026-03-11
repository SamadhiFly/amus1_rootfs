#!/bin/bash

db_name=/home/bird/data/historical_status.sqlite
table=acu_status 
schema_version=1

create_db()
{
  db_filename=$1
  if [ -f "$db_filename" ]; then
    echo "WARNING: $db_filename is already initialized!"
		return
	fi
  sqlite3 $db_filename "pragma jounal_mode=wal"
}

sql_command()
{
  db_filename=$1
  sql="$2"
  sqlite3 $db_filename "$sql"
}

check_table()
{
  db_filename=$1
  table_name=$2

  result=$(sqlite3 $db_filename "select name from sqlite_master where name = '$table_name'")
  if [ ! -z "$result" ]; then
    return 0
  fi

  return 1
}

create_table()
{
		sql="CREATE TABLE $table( \
  		id                                integer primary key autoincrement, \
  		utc_time_ms                       INT64 NOT NULL, \
  		acu_uptime_s                      INT NOT NULL, \
  		product_state_percent             JSON NOT NULL, \
  		product_state_id                  CHAR NOT NULL, \
  		previous_product_state_id         CHAR NOT NULL, \
  		mute_percent                      FLOAT NOT NULL, \
  		mf_active_carrier_id              INT NOT NULL, \
  		sat_longitude_degs                FLOAT NOT NULL, \
  		sat_skew_offset_degs              FLOAT NOT NULL, \
  		rx_polarisation                   JSON NOT NULL, \
  		rx_rf_freq_khz                    INT NOT NULL, \
  		rx_l_freq_khz                     INT NOT NULL, \
  		rx_bandwidth_khz                  INT NOT NULL, \
  		openamip_state_percent           JSON NOT NULL, \
  		modem_locked_percent              FLOAT NOT NULL, \
  		modem_con_db_min                  FLOAT NOT NULL, \
  		modem_con_db_max                  FLOAT NOT NULL, \
  		modem_con_db_avg                  FLOAT NOT NULL, \
  		modem_sat_longitude_degs          FLOAT NOT NULL, \
  		modem_sat_skew_offset_degs        FLOAT NOT NULL, \
  		modem_rx_polarisation             JSON NOT NULL, \
  		modem_rx_rf_freq_khz              INT NOT NULL, \
  		modem_rx_l_freq_khz               INT NOT NULL, \
  		modem_rx_bandwidth_khz            INT NOT NULL, \
  		modem_tx_rf_freq_khz              INT NOT NULL, \
  		modem_tx_l_freq_khz               INT NOT NULL, \
  		modem_tx_bandwidth_khz            INT NOT NULL, \
  		cpu_temperature_degc_avg          FLOAT NOT NULL, \
  		air_temperature_degc_avg          FLOAT NOT NULL, \
  		buc_dc_v_min                      FLOAT NOT NULL, \
  		buc_dc_v_max                      FLOAT NOT NULL, \
  		buc_dc_v_avg                      FLOAT NOT NULL, \
  		buc_dc_a_min                      FLOAT NOT NULL, \
  		buc_dc_a_max                      FLOAT NOT NULL, \
  		buc_dc_a_avg                      FLOAT NOT NULL, \
  		lnb_dc_v_min                      FLOAT NOT NULL, \
  		lnb_dc_v_max                      FLOAT NOT NULL, \
  		lnb_dc_v_avg                      FLOAT NOT NULL, \
  		lnb_dc_a_min                      FLOAT NOT NULL, \
  		lnb_dc_a_max                      FLOAT NOT NULL, \
  		lnb_dc_a_avg                      FLOAT NOT NULL, \
  		amu_dc_v_min                      FLOAT NOT NULL, \
  		amu_dc_v_max                      FLOAT NOT NULL, \
  		amu_dc_v_avg                      FLOAT NOT NULL, \
  		gps_locked_percent                FLOAT NOT NULL, \
  		gps_longitude_degs                FLOAT NOT NULL, \
  		gps_latitude_degs                 FLOAT NOT NULL, \
  		gps_speed_knots                   FLOAT NOT NULL, \
  		gps_heading_degs                  FLOAT NOT NULL, \
  		az_nominal_degs                   FLOAT NOT NULL, \
  		el_nominal_degs                   FLOAT NOT NULL, \
  		sk_nominal_degs                   FLOAT NOT NULL, \
  		satellite_in_view                 BOOL NOT NULL, \
  		cpn_db_min                        FLOAT NOT NULL, \
  		cpn_db_max                        FLOAT NOT NULL, \
  		cpn_db_avg                        FLOAT NOT NULL, \
  		demod_con_db_min                  FLOAT NOT NULL, \
  		demod_con_db_max                  FLOAT NOT NULL, \
  		demod_con_db_avg                  FLOAT NOT NULL, \
  		demod_locked_percent              FLOAT NOT NULL, \
  		tracking_stable_percent           FLOAT NOT NULL, \
  		buc_fault_percent                 FLOAT NOT NULL, \
  		lnb_fault_percent                 FLOAT NOT NULL, \
  		antenna_fault_percent             FLOAT NOT NULL, \
  		sensor_fault_percent              FLOAT NOT NULL, \
  		motor_fault_percent               FLOAT NOT NULL, \
  		gps_fault_percent                 FLOAT NOT NULL, \
  		blocking_percent                  FLOAT NOT NULL, \
  		az_pointing_error_degs_avg        FLOAT NOT NULL, \
  		az_pointing_error_degs_max        FLOAT NOT NULL, \
  		el_pointing_error_degs_avg        FLOAT NOT NULL, \
  		el_pointing_error_degs_max        FLOAT NOT NULL, \
  		rl_pointing_error_degs_avg        FLOAT NOT NULL, \
  		rl_pointing_error_degs_max        FLOAT NOT NULL, \
  		boresight_pointing_error_degs_avg FLOAT NOT NULL, \
  		boresight_pointing_error_degs_max FLOAT NOT NULL, \
  		skew_pointing_error_degs_avg      FLOAT NOT NULL, \
  		skew_pointing_error_degs_max      FLOAT NOT NULL, \
  		az_encoder_degs_avg               FLOAT NOT NULL, \
  		el_encoder_degs_avg               FLOAT NOT NULL, \
  		rl_encoder_degs_avg               FLOAT NOT NULL, \
  		az_current_position_degs_min      FLOAT NOT NULL, \
  		az_current_position_degs_max      FLOAT NOT NULL, \
  		az_current_position_degs_avg      FLOAT NOT NULL, \
  		el_current_position_degs_min      FLOAT NOT NULL, \
  		el_current_position_degs_max      FLOAT NOT NULL, \
  		el_current_position_degs_avg      FLOAT NOT NULL, \
  		rl_current_position_degs_min      FLOAT NOT NULL, \
  		rl_current_position_degs_max      FLOAT NOT NULL, \
  		rl_current_position_degs_avg      FLOAT NOT NULL, \
  		sk_current_position_degs          FLOAT NOT NULL, \
  		ant_current_substate              CHAR NOT NULL, \
  		ant_state_percent                 JSON NOT NULL \
		)"

		sqlite3 $db_name "begin; $sql; commit"
}

create_db $db_name

if ! check_table $table ; then
	create_table
	exit 0
fi

existing_schema_version=$(sqlite3 $db_file "select version from schema_version limit 1;")

if [ "$existing_schema_version" != "$schema_version" ]; then
	sqlite3 $db_name "begin; drop table $table; commit;"
	create_table
	exit 0
fi

