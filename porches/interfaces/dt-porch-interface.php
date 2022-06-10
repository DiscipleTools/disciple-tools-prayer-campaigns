<?php

interface DT_Porch_Interface {

    public function get_porch_details();

    public function load_admin(): DT_Porch_Admin_Menu_Interface;
}
