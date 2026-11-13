<?php

declare(strict_types=1);

namespace Modules\School\Http\Requests;

use App\Cms\Support\CmsRequest;
use LogicException;
use Modules\School\Http\Controllers\Admin\SchoolCrudController;

/** Validasi semua data sekolah: aturannya ditulis di controller resource masing-masing, di samping field form-nya. */
class SchoolRequest extends CmsRequest
{
    public function rules(): array
    {
        $controller = $this->route()?->getController();

        // Tanpa aturan = tanpa validasi; lebih baik gagal keras daripada menyimpan input mentah.
        if (! $controller instanceof SchoolCrudController) {
            throw new LogicException('SchoolRequest hanya untuk SchoolCrudController.');
        }

        return $controller->rules();
    }
}
