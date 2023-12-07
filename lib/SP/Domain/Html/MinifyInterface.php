<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Domain\Html;

use SP\Infrastructure\File\FileHandlerInterface;

/**
 * Interface MinifyInterface
 */
interface MinifyInterface
{
    /**
     * Devolver al navegador archivos CSS y JS comprimidos
     * Método que devuelve un recurso CSS o JS comprimido. Si coincide el ETAG se
     * devuelve el código HTTP/304
     */
    public function getMinified(): void;

    /**
     * Añadir un archivo
     *
     * @param FileHandlerInterface $fileHandler
     * @param bool $minify Si es necesario reducir
     *
     * @return MinifyInterface
     */
    public function addFile(FileHandlerInterface $fileHandler, bool $minify = true): MinifyInterface;

    /**
     * @param FileHandlerInterface[] $files
     * @param bool $minify
     * @return MinifyInterface
     */
    public function addFiles(array $files, bool $minify = true): MinifyInterface;

    public function builder(): MinifyInterface;
}