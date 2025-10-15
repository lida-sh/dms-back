<?php

namespace App\Http\Controllers\V1;

use App\Architecture;
use App\Http\Controllers\Controller;
use App\Http\Controllers\V1\Admin\ApiController;
use App\Http\Resources\ProcedureClientResource;
use App\Http\Resources\ProcessClientResource;
use App\Http\Resources\ProcessResource;
use App\Http\Resources\SubProcessClientResource;
use App\Procedure;
use App\Process;
use App\ProcessFile;
use App\Services\PdfSearchService;
use App\SubProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
class SearchController extends ApiController
{
    public function getArchitectures()
    {
        $architectures = Architecture::all();
        return response()->json($architectures, 200);
        // return $this->successResponse($architectures, 200);
    }
    public function getProcessesOfArchitecture(Architecture $architecture)
    {
        $processes = $architecture->processes;
        return $this->successResponse(ProcessResource::collection($processes), 200);
    }
    public function doAdvancedSearch(Request $request)
    {
        $wordSearch = $request->input("wordSearch");
        $architecture_id = $request->input("architecture_id");
        $process_id = $request->input("process_id");
        $itemSearch = $request->input("itemSearch");
        $docType = $request->input("docType");
        // return $request->all();

        if ($itemSearch === "code") {
            switch ($docType) {
                case "process":
                    $processes = Process::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->paginate(2);
                    // return response()->json($processes, 200);
                    return $this->successResponse([
                        "processes" => ProcessClientResource::collection($processes->load("architecture")),
                        "links" => ProcessClientResource::collection($processes)->response()->getData()->links,
                        "meta" => ProcessClientResource::collection($processes)->response()->getData()->meta
                    ], 200);
                case "subProcess":
                    $processes = SubProcess::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->paginate(4);
                    return $this->successResponse([
                        "subProcesses" => SubProcessClientResource::collection($processes->load(["architecture", "process"])),
                        "links" => SubProcessClientResource::collection($processes)->response()->getData()->links,
                        "meta" => SubProcessClientResource::collection($processes)->response()->getData()->meta
                    ], 200);
                case "procedure":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'procedures');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "instruction":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'instructions');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "contract":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'contracts');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "form":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'forms');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "regulation":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('code', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'regulations');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                default:
                    return $this->successResponse([], 200);

            }
        } elseif ($itemSearch === "title" || $itemSearch == null) {
            switch ($docType) {
                case "process":
                    $processes = Process::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->paginate(10);
                    // return response()->json($processes, 200);
                    return $this->successResponse([
                        "processes" => ProcessClientResource::collection($processes->load("architecture")),
                        "links" => ProcessClientResource::collection($processes)->response()->getData()->links,
                        "meta" => ProcessClientResource::collection($processes)->response()->getData()->meta
                    ], 200);
                case "subProcess":
                    $processes = SubProcess::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->paginate(10);
                    return $this->successResponse([
                        "subProcesses" => SubProcessClientResource::collection($processes->load(["architecture", "process"])),
                        "links" => SubProcessClientResource::collection($processes)->response()->getData()->links,
                        "meta" => SubProcessClientResource::collection($processes)->response()->getData()->meta
                    ], 200);
                case "procedure":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'procedures');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "instruction":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'instruction');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "contract":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'contract');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "form":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'form');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                case "regulation":
                    $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
                        return $query->where("architecture_id", $architecture_id);
                    })->when($process_id, function ($query, $process_id) {
                        return $query->where("process_id", $process_id);
                    })->when($wordSearch, function ($query, $wordSearch) {
                        return $query->where('title', 'LIKE', "%{$wordSearch}%");
                    })->when($docType, function ($query, $docType) {
                        return $query->where('docType', 'regulation');
                    })->paginate(4);
                    return $this->successResponse([
                        "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ], 200);
                default:

                    return $this->successResponse([], 200);

            }
        } elseif ($itemSearch === "files") {
             
            switch ($docType) {
                case "process":
                    $files = ProcessFile::whereHas('process', function ($query) use($architecture_id) {
                        $query->where('architecture_id', $architecture_id);
                    })->where('fileName', 'like', '%.pdf')->with('process:id,title')->get();
                    $fileSearch = new PdfSearchService();
                    $result = $fileSearch->searchFilesByArchitecture($files, $wordSearch);
                    // dd($docType);
                    break;
                case "subProcess":
                    break;
                case "procedure":
                    break;
                case "instruction":
                    break;
                case "contract":
                    break;
                case "form":
                    break;
                case "regulation":
                    break;
                default:
                    break;


            }
            return response()->json($result);
        }

    }
    public function doSearch(Request $request)
    {
        $wordSearch = $request->input("search");
        switch ($wordSearch) {
            case Str::contains($wordSearch, "زیر فرایند") || Str::contains($wordSearch, "زیرفرایند"):
                if (Str::contains($wordSearch, "زیر فرایند")) {
                    $search = trim(str_replace("زیر فرایند" . ' ', '', $wordSearch));
                } else if (Str::contains($wordSearch, "زیرفرایند")) {
                    $search = trim(str_replace("زیرفرایند" . ' ', '', $wordSearch));
                }
                $subProcesses = SubProcess::where('title', 'LIKE', "%{$search}%")->where("status", 1)->paginate(4);
                $data = collect([
                    "subProcesses" => SubProcessClientResource::collection($subProcesses->load(["architecture", "process"])),
                    "procedures" => [],
                    "processes" => [],
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => SubProcessClientResource::collection($subProcesses)->response()->getData()->links,
                    "meta" => SubProcessClientResource::collection($subProcesses)->response()->getData()->meta
                ], 200);
            case Str::contains($wordSearch, "فرایند"):
                $search = trim(str_replace("فرایند" . ' ', '', $wordSearch));
                $processes = Process::where('title', 'LIKE', "%{$search}%")->where("status", 1)->paginate(4);
                $data = collect([
                    "subProcesses" => [],
                    "procedures" => [],
                    "processes" => ProcessClientResource::collection($processes->load("architecture")),
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => ProcessClientResource::collection($processes)->response()->getData()->links,
                    "meta" => ProcessClientResource::collection($processes)->response()->getData()->meta
                ], 200);

            case Str::contains($wordSearch, "روش اجرایی") || Str::contains($wordSearch, "روشاجرایی"):
                if (Str::contains($wordSearch, "روش اجرایی")) {
                    $search = trim(str_replace("روش اجرایی" . ' ', '', $wordSearch));
                } else if (Str::contains($wordSearch, "روشاجرایی")) {
                    $search = trim(str_replace("روشاجرایی" . ' ', '', $wordSearch));
                }
                // $wordSearch = preg_replace('/\b' . preg_quote("روش اجرایی", '/') . '\b\s*/', '', $wordSearch);
                $procedures = Procedure::where('title', 'LIKE', "%{$search}%")->where("docType", "procedures")->where("status", 1)->paginate(4);
                $data = collect([
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    "subProcesses" => [],
                    "processes" => [],
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                    "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                ], 200);
            case Str::contains($wordSearch, "دستورالعمل"):
                $search = trim(str_replace("دستورالعمل" . ' ', '', $wordSearch));
                $procedures = Procedure::where('title', 'LIKE', "%{$search}%")->where("docType", "instruction")->where("status", 1)->paginate(4);
                $data = collect([
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    "subProcesses" => [],
                    "processes" => [],
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                    "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                ], 200);
            case Str::contains($wordSearch, "قرارداد"):
                $search = trim(str_replace("قرارداد" . ' ', '', $wordSearch));
                $procedures = Procedure::where('title', 'LIKE', "%{$search}%")->where("docType", "contract")->where("status", 1)->paginate(4);
                $data = collect([
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    "subProcesses" => [],
                    "processes" => [],
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                    "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                ], 200);
            case Str::contains($wordSearch, "فرم"):
                $search = trim(str_replace("فرم" . ' ', '', $wordSearch));
                $procedures = Procedure::where('title', 'LIKE', "%{$search}%")->where("docType", "form")->where("status", 1)->paginate(4);
                $data = collect([
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    "subProcesses" => [],
                    "processes" => [],
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                    "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                ], 200);
            case Str::contains($wordSearch, "آئین نامه") || Str::contains($wordSearch, "آئیننامه") || Str::contains($wordSearch, "آیین نامه") || Str::contains($wordSearch, "آییننامه"):
                if (Str::contains($wordSearch, "آئین نامه")) {
                    $search = trim(str_replace("آئین نامه" . ' ', '', $wordSearch));
                } else if (Str::contains($wordSearch, "آیین نامه")) {
                    $search = trim(str_replace("آیین نامه" . ' ', '', $wordSearch));
                } else if (Str::contains($wordSearch, "آئیننامه")) {
                    $search = trim(str_replace("آئیننامه" . ' ', '', $wordSearch));
                } else if (Str::contains($wordSearch, "آییننامه")) {
                    $search = trim(str_replace("آییننامه" . ' ', '', $wordSearch));
                }
                $procedures = Procedure::where('title', 'LIKE', "%{$search}%")->where("docType", "regulation")->where("status", 1)->paginate(4);
                $data = collect([
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    "subProcesses" => [],
                    "processes" => [],
                ]);
                return $this->successResponse([
                    "data" => $data,
                    "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                    "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                ], 200);
            case Str::contains($wordSearch, "PS") || Str::contains($wordSearch, "ps"):
                $search = strtoupper(trim($wordSearch));
                $processes = Process::where('code', 'LIKE', "%{$search}%")->where("status", 1)->paginate(4);
                $data = collect([
                    "processes" => ProcessClientResource::collection($processes->load("architecture")),
                    "subProcesses" => [],
                    "procedures" => [],
                ]);
                return $this->successResponse(
                    [
                        "data" => $data,
                        "links" => ProcessClientResource::collection($processes)->response()->getData()->links,
                        "meta" => ProcessClientResource::collection($processes)->response()->getData()->meta
                    ],
                    200
                );
            case Str::contains($wordSearch, "SP") || Str::contains($wordSearch, "sp"):
                $search = strtoupper(trim($wordSearch));
                $subProcesses = SubProcess::where('code', 'LIKE', "%{$search}%")->where("status", 1)->paginate(4);
                $data = collect([
                    "subProcesses" => SubProcessClientResource::collection($subProcesses->load("architecture")),
                    "procedures" => [],
                    "processes" => [],
                ]);
                return $this->successResponse(
                    [
                        "data" => $data,
                        "links" => SubProcessClientResource::collection($subProcesses)->response()->getData()->links,
                        "meta" => SubProcessClientResource::collection($subProcesses)->response()->getData()->meta
                    ],
                    200
                );
            case Str::contains($wordSearch, "PR") || Str::contains($wordSearch, "pr") || Str::contains($wordSearch, "IN") || Str::contains($wordSearch, "in") || Str::contains($wordSearch, "FR") || Str::contains($wordSearch, "fr") || Str::contains($wordSearch, "CO") || Str::contains($wordSearch, "co") || Str::contains($wordSearch, "RE") || Str::contains($wordSearch, "re"):
                $search = strtoupper(trim($wordSearch));
                $procedures = Procedure::where('code', 'LIKE', "%{$search}%")->where("status", 1)->paginate(4);
                $data = collect([
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    "subProcesses" => [],
                    "processes" => [],
                ]);
                return $this->successResponse(
                    [
                        'data' => $data,
                        "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
                        "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
                    ],
                    200
                );
            default:
                $subProcesses = SubProcess::where('title', 'LIKE', "%{$wordSearch}%")->get()->map(function ($item) {
                    $item['type'] = 'زیر فرایند';
                    return $item;
                });
                $processes = Process::where('title', 'LIKE', "%{$wordSearch}%")->get()->map(function ($item) {
                    $item['type'] = 'فرایند';
                    return $item;
                });
                $procedures = Procedure::where('title', 'LIKE', "%{$wordSearch}%")->get()->map(function ($item) {
                    $item['type'] = 'روش اجرایی';
                    return $item;
                });

                $data = collect([
                    "subProcesses" => SubProcessClientResource::collection($subProcesses->load(["architecture", "process"])),
                    "processes" => ProcessClientResource::collection($processes->load("architecture")),
                    "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"]))
                ]);
                $mergedData = collect()->merge($data);
                // $allData = collect()->merge($data);
                // ->merge($subProcesses)
                // ->merge($processes)
                // ->merge($procedures);
                // $allData = $data->flatten();
                // dd($mergedData);
                $page = request('page', 1);
                $perPage = 10;
                $pagedData = new LengthAwarePaginator(
                    $mergedData->forPage($page, $perPage), // داده‌های صفحه جاری
                    $mergedData->count(), // تعداد کل آیتم‌ها
                    $perPage, // تعداد در هر صفحه
                    $page, // شماره صفحه جاری
                    ['path' => request()->url(), 'query' => request()->query()] // URL صفحه‌بندی
                );
                $allData = collect()->merge($data)->merge([
                    'meta' => [
                        'current_page' => $pagedData->currentPage(),
                        'last_page' => $pagedData->lastPage(),
                        'per_page' => $pagedData->perPage(),
                        'total' => $pagedData->total(),
                    ],
                    'links' => [
                        'first' => $pagedData->url(1),
                        'last' => $pagedData->url($pagedData->lastPage()),
                        'prev' => $pagedData->previousPageUrl(),
                        'next' => $pagedData->nextPageUrl(),
                    ],
                ]);
                return $this->successResponse([
                    'data' => $data,
                    // "subProcesses" => SubProcessClientResource::collection($subProcesses->load(["architecture", "process"])),
                    // "processes" => ProcessClientResource::collection($processes->load("architecture")),
                    // "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
                    'meta' => $allData['meta'],
                    'links' => $allData['links'],
                ], 200);
            // return $this->successResponse([], 200);

        }
    }
}
