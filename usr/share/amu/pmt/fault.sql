/*
Fault DB schema for SQLite3

Author: Mickey
Date: 2020-02-28
Version: 1
Modify: zhangbingan
Date: 2020-02-29
Version:3
*/

CREATE TABLE fault
(
  update_time_us BIGINT NOT NULL,
  code TEXT NOT NULL,
  item TEXT NOT NULL,
  fault_on INTEGER NOT NULL DEFAULT 0,
  arguments JSON NOT NULL,
  PRIMARY KEY (code)
);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0206', 'OpenAMIP link failed', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0207', 'BMC and BMP link failed', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0209', 'ACU and ANT no communication', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0211', 'ACU tracking unstable', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0301', 'DC 1.0V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0302', 'DC 1.2V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0303', 'DC 1.5V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0304', 'DC 1.8V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0306', 'DC 3.5V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0307', 'DC 5V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0309', 'DC 24V fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0311', 'DC LNB fault', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0401', 'DC current fault', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0403', 'LNB current fault', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0501', 'Tuner init failed', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0502', 'Demod init failed', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E0601', 'CPU over temperature', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E2101', 'GPS lost signal', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E3101', 'LNB no current', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E3102', 'LNB over current', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E4001', 'Supply voltage is too high', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E4002', 'Supply voltage is too low', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E4101', 'ETH0 link down', 0, '{}', 0);
INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E4102', 'ETH1 link down', 0, '{}', 0);

INSERT INTO fault (code, item, fault_on, arguments, update_time_us) VALUES ('E4202', 'Modem no data', 0, '{}', 0);

