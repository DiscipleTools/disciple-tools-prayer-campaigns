<?php

interface IDT_Porch_Loader {

    public function get_porch_details();

    public function load_admin(): IDT_Porch_Admin_Menu;

    public function load_porch();
}
