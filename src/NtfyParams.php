<?php

namespace Logger\Ntfy;

class NtfyParams {
	/**
	 * @param list<string> $tags
	 * @param array<int, array<string, scalar|null>|string>|string|null $actions
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
	 * @param array<string, scalar|list<string>|array<int, array<string, scalar|null>|string>|null> $values
	 */
	public static function fromArray(array $values): self {
		return new self(
			title: isset($values['title']) ? (string)$values['title'] : null,
			priority: self::normalizePriority($values['priority'] ?? null),
			tags: self::normalizeTags($values['tags'] ?? []),
			markdown: isset($values['markdown']) ? (bool)$values['markdown'] : null,
			click: isset($values['click']) ? (string)$values['click'] : null,
			attach: isset($values['attach']) ? (string)$values['attach'] : null,
			filename: isset($values['filename']) ? (string)$values['filename'] : null,
			icon: isset($values['icon']) ? (string)$values['icon'] : null,
			actions: self::normalizeActions($values['actions'] ?? null),
			delay: isset($values['delay']) ? (string)$values['delay'] : null,
			email: isset($values['email']) ? (string)$values['email'] : null,
			call: isset($values['call']) ? (string)$values['call'] : null,
			topic: isset($values['topic']) ? (string)$values['topic'] : null,
			sequenceId: isset($values['sequence_id']) ? (string)$values['sequence_id'] : null,
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

	private static function normalizePriority(mixed $priority): int|string|null {
		if($priority === null || is_int($priority) || is_string($priority)) {
			return $priority;
		}

		return (string)$priority;
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

		return array_values(array_map(static fn(mixed $tag): string => (string)$tag, $tags));
	}

	/**
	 * @return array<int, array<string, scalar|null>|string>|string|null
	 */
	private static function normalizeActions(mixed $actions): array|string|null {
		if($actions === null || is_string($actions) || is_array($actions)) {
			return $actions;
		}

		return (string)$actions;
	}
}
