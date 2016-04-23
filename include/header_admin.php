<?php

/*
 * Copyright (c) 2016 ricky <https://github.com/rickyepoderi/runnerupweb>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use runnerupweb\common\Logging;

// check normal session configuration
include_once('header_session.php');

// check it is an admin
if ($user->getRole() !== 'ADMIN') {
    // user not amin => 403
    Logging::debug("User is not an admin!");
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 403 Forbidden');
    exit;
}
