<?hh

namespace HHVM\UserDocumentation;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\ParamTag;
use phpDocumentor\Reflection\DocBlock\Tag;

class FunctionMarkdownBuilder {
  use DocblockTagReader;

  private FunctionYAML $yaml;
  protected ?DocBlock $docblock;

  public function __construct(
      ?string $file = null,
      ?FunctionDocumentation $method = null,
      private ?string $class = null,
      ) {
    $this->yaml = \Spyc::YAMLLoad($file);
    if ($method) {
      $this->yaml['data'] = $method;
    }

    $comment = $this->yaml['data']['docComment'];
    if ($comment !== null) {
      $this->docblock = new DocBlock($comment);
    }
  }

  public function build(): string {
    return implode(
      "\n\n",
      [
        $this->getHeading(),
        $this->getDescription(),
        $this->getParameters(),
        $this->getExamples(),
      ],
    )."\n";
  }

  private function getHeading(): ?string {
    if (
        $this->docblock?->getText() !== $this->docblock?->getShortDescription()
       ) {
      return $this->docblock?->getShortDescription();
    }
    return null;
  }

  private function getDescription(): string {
    $md = "### Description\n\n";

    $md .= "```Hack\n".$this->getSignature()."\n```\n\n";

    $md .= $this->docblock?->getText();

    return $md;
  }

  private function getParameters(): string {

    // If no parameters for the function, then move on
    if (count($this->yaml['data']['parameters']) === 0) {
      return "";
    }

    $tags = $this->getParamTags();

    $md = "### Parameters\n\n";

    foreach ($this->yaml['data']['parameters'] as $param) {
      $name = $param['name'];

      // The keys in the $tags map are actually the parameter names with the
      // $. Why? I don't know.
      $tag = idx($tags, '$'.$name);

      $md .= '- `'.Stringify::parameter($param, $tag).'`';
      if ($tag) {
        // The '-' is generally included in the description. We can do something
        // more fancy if we need to, like `: ` and a preg_match on the first
        // position of a alphnumeric character, thus getting rid of any '-'
        $md .= ' '.$tag->getDescription();
      }
      $md .= "\n";
    }
    return $md;
  }

  private function getSignature(): string {
    $ret = '';

    $visibility = $this->yaml['data']['visibility'];
    if ($visibility !== null) {
      $ret .= $visibility.' ';
    }
    $ret .= 'function '.$this->yaml['data']['name'];

    $tags = $this->getParamTags();
    $params = array_map(
        $param ==> Stringify::parameter($param, idx($tags, $param['name'])),
        $this->yaml['data']['parameters'],
        );
    $ret .= '('.implode(', ', $params).')';

    $return_type = $this->yaml['data']['returnType'];
    if ($return_type !== null) {
      $ret .= ': '.Stringify::typehint($return_type);
    }

    return $ret;
  }

  private function getExamples(): ?string {
    $path = LocalConfig::ROOT.'/guides/hack/99-api-examples/';

    if ($this->class === null) {
      $path .= 'function.';
    } else {
      $path .= 'class.'.$this->class.'/';
    }

    $path .= $this->yaml['data']['name'];
    $path = strtr($path, "\\", '.');
    $examples = glob($path.'/*.php');
    if (count($examples) === 0) {
      return null;
    }
    sort($examples);

    $ret = "### Examples";
    foreach ($examples as $example) {
      $preamble = dirname($example).'/'.basename($example, '.php').'.md';
      if (file_exists($preamble)) {
        $ret .= "\n\n".file_get_contents($preamble)."\n\n";
      }
      $ret .= "\n\n@@ ".$example." @@";
    }
    return $ret;
  }

  private function getParamTags(): Map<string, ParamTag> {
    $tags_vec = $this->getTagsByName('param', ParamTag::class);
    $tags = Map { };
    foreach ($tags_vec as $tag) {
      $tags[$tag->getVariableName()] = $tag;
    }
    return $tags;
  }
}
