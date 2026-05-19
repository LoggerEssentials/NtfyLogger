<?php

namespace Logger\Ntfy;

use Stringable;

/**
 * @phpstan-type NtfyAction array<string, bool|float|int|string|null>
 * @phpstan-type NtfyActions array<int, NtfyAction|string>
 */
class NtfyParams {
	/**
	 * @param list<string> $tags
	 * @param NtfyActions|string|null $actions
	 */
	public function __construct(
		public ?string $title = null,
		public int|string|null $priority = null,
		public array $tags = [],
		public ?bool $markdown = null,
		public ?string $click = null,
		public ?string $attach = null,
		public ?string $filename = null,
		public ?string $icon = null,
		public array|string|null $actions = null,
		public ?string $delay = null,
		public ?string $email = null,
		public ?string $call = null,
		public ?string $topic = null,
		public ?string $sequenceId = null,
		public ?bool $cache = null,
		public ?bool $firebase = null,
		public ?bool $unifiedPush = null,
	) {}

	/**
	 * @param array<array-key, mixed> $values
	 */
	public static function fromArray(array $values): self {
		return new self(
			title: self::normalizeString($values['title'] ?? null),
			priority: self::normalizePriority($values['priority'] ?? null),
			tags: self::normalizeTags($values['tags'] ?? []),
			markdown: isset($values['markdown']) ? (bool)$values['markdown'] : null,
			click: self::normalizeString($values['click'] ?? null),
			attach: self::normalizeString($values['attach'] ?? null),
			filename: self::normalizeString($values['filename'] ?? null),
			icon: self::normalizeString($values['icon'] ?? null),
			actions: self::normalizeActions($values['actions'] ?? null),
			delay: self::normalizeString($values['delay'] ?? null),
			email: self::normalizeString($values['email'] ?? null),
			call: self::normalizeString($values['call'] ?? null),
			topic: self::normalizeString($values['topic'] ?? null),
			sequenceId: self::normalizeString($values['sequence_id'] ?? null),
			cache: isset($values['cache']) ? (bool)$values['cache'] : null,
			firebase: isset($values['firebase']) ? (bool)$values['firebase'] : null,
			unifiedPush: isset($values['unified_push']) ? (bool)$values['unified_push'] : null,
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function toHeaders(): array {
		$headers = [];
		$this->addHeader($headers, 'Title', $this->title);
		$this->addHeader($headers, 'Priority', $this->priority);
		$this->addHeader($headers, 'Tags', $this->tags !== [] ? implode(',', $this->tags) : null);
		$this->addHeader($headers, 'Markdown', $this->markdown);
		$this->addHeader($headers, 'Click', $this->click);
		$this->addHeader($headers, 'Attach', $this->attach);
		$this->addHeader($headers, 'Filename', $this->filename);
		$this->addHeader($headers, 'Icon', $this->icon);
		$this->addHeader($headers, 'Actions', $this->formatActions());
		$this->addHeader($headers, 'Delay', $this->delay);
		$this->addHeader($headers, 'Email', $this->email);
		$this->addHeader($headers, 'Call', $this->call);
		$this->addHeader($headers, 'X-Sequence-ID', $this->sequenceId);
		$this->addHeader($headers, 'Cache', $this->formatNegativeBoolean($this->cache));
		$this->addHeader($headers, 'Firebase', $this->formatNegativeBoolean($this->firebase));
		$this->addHeader($headers, 'UnifiedPush', $this->unifiedPush);

		return $headers;
	}

	/**
	 * @param array<string, string> $headers
	 */
	private function addHeader(array &$headers, string $name, bool|int|string|null $value): void {
		if($value === null || $value === '') {
			return;
		}

		$headers[$name] = match($value) {
			true => 'yes',
			false => 'no',
			default => (string)$value,
		};
	}

	private function formatActions(): ?string {
		if($this->actions === null || $this->actions === '') {
			return null;
		}
		if(is_string($this->actions)) {
			return $this->actions;
		}

		$json = json_encode($this->actions, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return $json === '[]' ? null : $json;
	}

	private function formatNegativeBoolean(?bool $value): ?string {
		if($value === null) {
			return null;
		}

		return $value ? 'yes' : 'no';
	}

	private static function normalizeString(mixed $value): ?string {
		if($value === null) {
			return null;
		}
		if(is_scalar($value) || $value instanceof Stringable) {
			return (string)$value;
		}

		return null;
	}

	private static function normalizePriority(mixed $priority): int|string|null {
		if($priority === null || is_int($priority) || is_string($priority)) {
			return $priority;
		}
		if(is_float($priority) || is_bool($priority) || $priority instanceof Stringable) {
			return (string)$priority;
		}

		return null;
	}

	/**
	 * @return list<string>
	 */
	private static function normalizeTags(mixed $tags): array {
		if(is_string($tags)) {
			return array_values(array_filter(array_map('trim', explode(',', $tags))));
		}
		if(!is_array($tags)) {
			return [];
		}

		$normalizedTags = [];
		foreach($tags as $tag) {
			if(is_scalar($tag) || $tag instanceof Stringable) {
				$tag = trim((string)$tag);
				if($tag !== '') {
					$normalizedTags[] = $tag;
				}
			}
		}

		return $normalizedTags;
	}

	/**
	 * @return NtfyActions|string|null
	 */
	private static function normalizeActions(mixed $actions): array|string|null {
		if($actions === null || is_string($actions)) {
			return $actions;
		}
		if(is_scalar($actions) || $actions instanceof Stringable) {
			return (string)$actions;
		}
		if(!is_array($actions)) {
			return null;
		}

		$normalizedActions = [];
		foreach($actions as $action) {
			if(is_string($action)) {
				$normalizedActions[] = $action;
				continue;
			}
			if(!is_array($action)) {
				continue;
			}

			$normalizedAction = [];
			foreach($action as $key => $value) {
				if(is_string($key) && ($value === null || is_scalar($value))) {
					$normalizedAction[$key] = $value;
				}
			}
			if($normalizedAction !== []) {
				$normalizedActions[] = $normalizedAction;
			}
		}

		return $normalizedActions;
	}
}
