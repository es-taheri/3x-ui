<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Subscription balancer endpoints.
 *
 * A balancer is emitted into the JSON subscription of every client that sits on
 * at least one selected inbound, as one extra config document with
 * `routing.balancers` and a `burstObservatory`.
 */
class SubBalancers
{
    /** Strategy: pick the member with the most stable load. */
    public const STRATEGY_LEAST_LOAD = 'leastLoad';

    /** Strategy: pick the member with the lowest latency. */
    public const STRATEGY_LEAST_PING = 'leastPing';

    /** Strategy: pick a random member. */
    public const STRATEGY_RANDOM = 'random';

    /** Strategy: rotate through members in order. */
    public const STRATEGY_ROUND_ROBIN = 'roundRobin';

    /**
     * @param Request $request Shared HTTP transport.
     */
    public function __construct(
        /** @var Request Shared HTTP transport. */
        private readonly Request $request
    )
    {
    }

    /**
     * List every balancer in sort order.
     *
     * @return Result
     */
    public function list(): Result
    {
        return $this->_request(Request::get(''));
    }

    /**
     * Create a balancer.
     *
     * @param string $remark Display label used as the config remark.
     * @param array $inbound_ids Member inbound ids.
     * @param string $strategy One of the STRATEGY_* constants.
     * @param int $sort_order 1-based position in the subscription list.
     * @param bool $enabled Whether the balancer is emitted.
     * @param array $member_weights `leastLoad` only: inbound id => weight.
     * @return Result
     */
    public function create(
        string $remark,
        array $inbound_ids,
        string $strategy = self::STRATEGY_RANDOM,
        int $sort_order = 1,
        bool $enabled = true,
        array $member_weights = []
    ): Result
    {
        return $this->_request(Request::post('', form_params: $this->payload(
            $remark, $inbound_ids, $strategy, $sort_order, $enabled, $member_weights
        )));
    }

    /**
     * Replace a balancer.
     *
     * Omitting `member_weights` clears the stored weights; omitting `enabled`
     * keeps the current value.
     *
     * @param int $id Balancer id.
     * @param string $remark Display label.
     * @param array $inbound_ids Member inbound ids.
     * @param string $strategy One of the STRATEGY_* constants.
     * @param int $sort_order 1-based position in the subscription list.
     * @param bool|null $enabled Whether the balancer is emitted.
     * @param array $member_weights `leastLoad` only: inbound id => weight.
     * @return Result
     */
    public function update(
        int $id,
        string $remark,
        array $inbound_ids,
        string $strategy = self::STRATEGY_RANDOM,
        int $sort_order = 1,
        ?bool $enabled = null,
        array $member_weights = []
    ): Result
    {
        $form = $this->payload($remark, $inbound_ids, $strategy, $sort_order, $enabled, $member_weights);
        return $this->_request(Request::post((string)$id, form_params: $form));
    }

    /**
     * Delete a balancer.
     *
     * @param int $id Balancer id.
     * @return Result
     */
    public function delete(int $id): Result
    {
        return $this->_request(Request::post("$id/del"));
    }

    /**
     * Build the form payload shared by create and update.
     *
     * @param string $remark Display label.
     * @param array $inbound_ids Member inbound ids.
     * @param string $strategy Strategy name.
     * @param int $sort_order Sort order.
     * @param bool|null $enabled Enabled flag.
     * @param array $member_weights Member weights.
     * @return array
     */
    private function payload(
        string $remark,
        array $inbound_ids,
        string $strategy,
        int $sort_order,
        ?bool $enabled,
        array $member_weights
    ): array
    {
        $form = [
            'remark' => $remark,
            'strategy' => $strategy,
            'inboundIds' => array_values(array_map('intval', $inbound_ids)),
            'sortOrder' => $sort_order,
        ];
        if (!is_null($enabled)) {
            $form['enabled'] = $enabled ? 'true' : 'false';
        }
        if ($member_weights !== []) {
            $form['memberWeights'] = json_encode($member_weights) ?: '{}';
        }
        return $form;
    }

    /**
     * Send one balancer request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->sub_balancers($request);
        return $result;
    }
}
