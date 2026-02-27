<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Modules\V1\Kitchen\Models\KitchenOrder;
use Modules\V1\Kitchen\Resources\KitchenOrderResource;
use Modules\V1\Kitchen\Services\KitchenOrderService;
use Modules\V1\Kitchen\Services\OrderProcessingService;
use Modules\V1\Kitchen\Services\DeliveryService;
use Shared\Helpers\ResponseHelper;

final class KitchenController extends Controller
{
    public function __construct(
        private KitchenOrderService $orderService,
        private OrderProcessingService $processingService,
        private DeliveryService $deliveryService
    ) {}

    /**
     * @OA\Post(
     *      path="/kitchen/list",
     *      summary="Get all kitchen orders",
     *      description="Returns a paginated list of kitchen orders",
     *      tags={"Kitchen Orders"},
     *
     *      @OA\RequestBody(
     *
     *          @OA\MediaType(
     *              mediaType="application/json",
     *
     *              @OA\Schema(
     *
     *                  @OA\Property(property="pageNumber", type="integer", example=1, description="Page number"),
     *                  @OA\Property(property="pageSize", type="integer", example=10, description="Items per page"),
     *                  @OA\Property(property="sortDir", type="string", enum={"ASC", "DESC"}, example="ASC", description="Sort direction"),
     *                  @OA\Property(property="sortDirColumn", type="string", example="id", description="Column to sort by"),
     *                  @OA\Property(property="search", type="string", example="ORDER", description="Global search string")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function index(Request $request)
    {
        $pageNumber = $request->input('pageNumber', 1);
        $pageSize = $request->input('pageSize', 15);
        $sortDirColumn = $request->input('sortDirColumn', 'created_at');
        $sortDir = $request->input('sortDir', 'desc');
        $search = $request->input('search', '');

        $query = KitchenOrder::with(['dapur', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('dapur_id')) {
            $query->where('dapur_id', $request->dapur_id);
        }

        // Apply global search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting and pagination
        $offset = ($pageNumber - 1) * $pageSize;
        $orders = $query->orderBy($sortDirColumn, $sortDir)
                         ->offset($offset)
                         ->limit($pageSize)
                         ->get();

        return ResponseHelper::success(KitchenOrderResource::collection($orders));
    }

    /**
     * @OA\Post(
     *     path="/kitchen/detail/{id}",
     *     summary="Get kitchen order detail",
     *     description="Get detailed information about a specific kitchen order",
     *     tags={"Kitchen Orders"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitchen Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function show(KitchenOrder $order)
    {
        $order->load(['dapur', 'items']);

        return ResponseHelper::success(new KitchenOrderResource($order));
    }

    /**
     * @OA\Post(
     *     path="/kitchen/create",
     *     summary="Create kitchen order",
     *     description="Create a new draft kitchen order",
     *     tags={"Kitchen Orders"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"dapur_id", "items"},
     *
     *                 @OA\Property(property="dapur_id", type="string", format="uuid"),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"item_id", "quantity"},
     *                     @OA\Property(property="item_id", type="string", format="uuid"),
     *                     @OA\Property(property="quantity", type="integer")
     *                 )),
     *                 @OA\Property(property="notes", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function create(Request $request)
    {
        try {
            $request->merge(['created_by' => $request->user()?->id]);

            $order = $this->orderService->createDraftOrder($request->all());

            return ResponseHelper::success(
                data: new KitchenOrderResource($order),
                message: 'Kitchen order created successfully',
                status: 201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/kitchen/{id}/send",
     *     summary="Send kitchen order",
     *     description="Send a draft kitchen order for processing",
     *     tags={"Kitchen Orders"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitchen Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order sent successfully"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function send(Request $request, KitchenOrder $order)
    {
        try {
            $result = $this->orderService->sendOrder($order->id, $request->user()?->id);

            return ResponseHelper::success(
                data: new KitchenOrderResource($result),
                message: 'Order sent successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/kitchen/{id}/process",
     *     summary="Process kitchen order",
     *     description="Process order with FEFO stock allocation",
     *     tags={"Kitchen Orders"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitchen Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"items"},
     *
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     type="object",
     *                     required={"item_id", "quantity"},
     *                     @OA\Property(property="item_id", type="string", format="uuid"),
     *                     @OA\Property(property="quantity", type="integer")
     *                 ))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order processed successfully with FEFO allocation"
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Insufficient stock"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function process(Request $request, KitchenOrder $order)
    {
        try {
            $result = $this->processingService->processOrder($order->id, $request->items);

            return ResponseHelper::success(
                data: new KitchenOrderResource($result['order']),
                message: 'Order processed successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/kitchen/{id}/deliver",
     *     summary="Deliver kitchen order",
     *     description="Deliver order with QR code generation, stock reduction, and transaction recording",
     *     tags={"Kitchen Orders"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Kitchen Order UUID",
     *
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="delivered_by", type="string", example="John Doe"),
     *                 @OA\Property(property="notes", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order delivered successfully with QR code"
     *     ),
     *     security={
     *         {"bearerAuth": {}}
     *     }
     * )
     */
    public function deliver(Request $request, KitchenOrder $order)
    {
        try {
            $result = $this->deliveryService->deliverOrder($order->id, $request->all());

            return ResponseHelper::success(
                data: [
                    'order' => new KitchenOrderResource($result['order']),
                    'qr_code' => $result['qr_code'],
                    'qr_image_url' => $result['qr_image_url'],
                ],
                message: 'Order delivered successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
