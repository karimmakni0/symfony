<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PostFormType extends AbstractType
{
    // List of bad words to filter
    private const BAD_WORDS = ['fuck', 'shit', 'ass', 'bitch', 'damn', 'bastard', 'cunt', 'dick', 'asshole'];
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Blog Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter a title for your blog post'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Title must be at least {{ limit }} characters long',
                        'maxMessage' => 'Title cannot be longer than {{ limit }} characters',
                    ]),
                    new Callback([$this, 'validateNoBadWords']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Blog Content',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => '8',
                    'placeholder' => 'Write your blog post content here'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter some content',
                    ]),
                    new Length([
                        'min' => 20,
                        'minMessage' => 'Content must be at least {{ limit }} characters long',
                    ]),
                    new Callback([$this, 'validateNoBadWords']),
                ],
            ])
            ->add('activityId', ChoiceType::class, [
                'label' => 'Related Activity',
                'choices' => $options['activities_choices'],
                'required' => true,
                'placeholder' => '-- Select an Activity --',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select an activity',
                    ]),
                ],
            ])
            ->add('picture', FileType::class, [
                'label' => 'Blog Image',
                'mapped' => false,
                'required' => $options['image_required'],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'constraints' => $this->getImageConstraints($options['image_required']),
            ])
        ;
    }

    public function validateNoBadWords($value, ExecutionContextInterface $context): void
    {
        $text = strtolower($value);
        
        foreach (self::BAD_WORDS as $badWord) {
            if (strpos($text, $badWord) !== false) {
                $context->buildViolation('Your text contains inappropriate language. Please revise it.')
                    ->addViolation();
                break;
            }
        }
    }

    private function getImageConstraints(bool $required): array
    {
        $constraints = [];
        
        if ($required) {
            $constraints[] = new NotBlank([
                'message' => 'Please upload an image for your blog post',
            ]);
        }
        
        $constraints[] = new File([
            'maxSize' => '2M',
            'mimeTypes' => [
                'image/jpeg',
                'image/png',
                'image/gif',
            ],
            'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG or GIF)',
        ]);
        
        return $constraints;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'image_required' => true,
            'activities_choices' => [],
        ]);
    }
}
